<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentApprovalLog;
use App\Models\DocumentApprovalStep;
use App\Models\DocumentVersion;
use App\Models\SignatureRequest;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\ApprovalRouteResolved;
use App\Notifications\DirectorDocumentTembusanNotification;
use App\Notifications\DocumentApprovalRequested;
use App\Notifications\DocumentApprovalResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Signature-Driven Multi-Tier Approval Workflow Engine (Docuflow CMH)
 *
 * Principles:
 * 1. Driven by signature boxes ([ttd:user]) in document content or active SignatureRequest records.
 * 2. Ordered by hierarchy: Staff (10) -> Unit PIC (20) -> Head / PIC Klinik (30) -> Director (40) -> Admin (50).
 * 3. Smart Bypass: If document author is an assigned signer, their step is automatically bypassed.
 * 4. Fallback: If no signature boxes exist, systemic approval applies (PIC Unit -> PIC Klinik / Head).
 * 5. Director Rule:
 *    - With Director signature box: Blocking final approval step.
 *    - Without Director signature box: Finalized at Head / PIC Klinik, Director receives a copy (Tembusan / Only To Know).
 */
class ApprovalRoutingService
{
    /**
     * Compile and initialize multi-tier approval steps for a document version based on signature requests.
     *
     * @return array{steps: Collection<int, DocumentApprovalStep>, activeStep: ?DocumentApprovalStep, message: string}
     */
    public function compileWorkflowFromSignatures(Document $document, DocumentVersion $version, User $creator): array
    {
        return DB::transaction(function () use ($document, $version, $creator) {
            // Cancel / remove existing pending/waiting steps for this version
            DocumentApprovalStep::where('version_id', $version->id)->delete();

            $isPusat = (bool) ($document->branch?->is_pusat ?? false);
            if (!$document->branch_id && $document->company_id) {
                $isPusat = true;
            }

            // Reset any previous signature requests on this document to pending/unused for this new approval run
            SignatureRequest::where('document_id', $document->id)
                ->where('status', '!=', 'pending')
                ->update([
                    'status' => 'pending',
                    'is_used' => false,
                    'rejected_reason' => null,
                ]);

            // Fetch signature requests attached to this document
            $signatureRequests = SignatureRequest::where('document_id', $document->id)
                ->with(['targetUser.unitKerjas', 'targetUser.unitKerja', 'requestedSignature'])
                ->get();

            $stepsToCreate = [];
            $order = 1;

            if ($signatureRequests->isNotEmpty()) {
                // 1. Check if the document belongs to a Unit Kerja and if the Head of that Unit Kerja is already in signature requests
                $originUnitHead = null;
                $docUnitKerjaId = $document->unit_kerja_id ?? $creator->unit_kerja_id ?? ($creator->allUnitKerjaIds()[0] ?? null);
                $originUnit = $document->unitKerja ?? ($docUnitKerjaId ? UnitKerja::find($docUnitKerjaId) : null);
                if ($originUnit) {
                    if ($originUnit->pic_user_id) {
                        $originUnitHead = $originUnit->picUser;
                    } else {
                        $originUnitHead = User::where('system_role', 'head')
                            ->where('is_active', true)
                            ->where(function ($q) use ($originUnit) {
                                $q->where('unit_kerja_id', $originUnit->id)
                                  ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $originUnit->id));
                            })
                            ->first();
                    }
                }

                $isOriginHeadInSignatures = $originUnitHead && $signatureRequests->contains(fn($sr) => $sr->target_user_id === $originUnitHead->id);

                // Build raw step candidates
                $rawSteps = [];

                // If origin unit head exists and is NOT in signature requests, automatically add a review step for the unit head as FIRST STEP (Weight: 5)
                if ($originUnitHead && !$isOriginHeadInSignatures) {
                    $isCreator = ($creator->id === $originUnitHead->id);
                    $unitName = $originUnit?->nama_unit_kerja ?? 'Unit Kerja';

                    $rawSteps[] = [
                        'weight' => 5,
                        'signature_request_id' => null,
                        'step_type' => 'head_approval',
                        'step_name' => __('Review & Persetujuan Kepala Unit :unit (:name)', [
                            'unit' => $unitName,
                            'name' => $originUnitHead->name,
                        ]),
                        'assigned_user_id' => $originUnitHead->id,
                        'assigned_role' => 'head',
                        'status' => $isCreator ? 'bypassed' : 'waiting',
                        'action_by_id' => $isCreator ? $creator->id : null,
                        'action_at' => $isCreator ? now() : null,
                        'notes' => $isCreator ? __('Dilewati otomatis (Pembuat adalah Kepala Unit Kerja)') : null,
                    ];
                }

                // Add all steps from signature requests
                foreach ($signatureRequests as $sigReq) {
                    $targetUser = $sigReq->targetUser;
                    if (!$targetUser) {
                        continue;
                    }

                    $isCreator = ($creator->id === $targetUser->id);
                    $role = $targetUser->system_role;

                    // Calculate weight for sorting (Origin Head = 5, Staff = 10, Unit PIC = 20, Other Head = 30-32, Kacab = 35, Director = 40, Admin = 50)
                    $weight = 10;
                    if ($targetUser->isAdmin()) {
                        $weight = 50;
                    } elseif ($targetUser->isDirector()) {
                        $weight = 40;
                    } elseif ($targetUser->isPicKlinik()) {
                        $weight = 35;
                    } elseif ($targetUser->isHead()) {
                        $docUnitId = $document->unit_kerja_id;
                        $isDocUnitHead = $docUnitId && (
                            $targetUser->unit_kerja_id === $docUnitId ||
                            $targetUser->unitKerjas->pluck('id')->contains($docUnitId)
                        );

                        if ($isDocUnitHead) {
                            $weight = 5; // Origin unit head reviews and signs at Step 1 (first gatekeeper)
                        } else {
                            $isMutuHead = $targetUser->unitKerjas->contains(fn($uk) => $uk->kode_unit_kerja === '01' || str_contains(strtolower($uk->nama_unit_kerja), 'mutu'))
                                || ($targetUser->unitKerja && ($targetUser->unitKerja->kode_unit_kerja === '01' || str_contains(strtolower($targetUser->unitKerja->nama_unit_kerja), 'mutu')));

                            $weight = $isMutuHead ? 32 : 31;
                        }
                    } elseif (UnitKerja::where('pic_user_id', $targetUser->id)->exists()) {
                        $weight = 20;
                    }

                    // Determine step type & human-readable name
                    if ($targetUser->isDirector()) {
                        $stepType = 'director_approval';
                        $stepName = __('Pengesahan Direktur PT (:name)', ['name' => $targetUser->name]);
                    } elseif ($targetUser->isPicKlinik()) {
                        $stepType = 'pic_klinik_approval';
                        $stepName = __('Pengesahan Kepala Cabang :branch (:name)', [
                            'branch' => $document->branch?->name ?? 'Cabang',
                            'name' => $targetUser->name,
                        ]);
                    } elseif ($targetUser->isHead()) {
                        if ($document->branch && $document->branch->pic_klinik_id === $targetUser->id) {
                            $stepType = 'pic_klinik_approval';
                            $stepName = __('Pengesahan Kepala Cabang :branch (:name)', [
                                'branch' => $document->branch->name,
                                'name' => $targetUser->name,
                            ]);
                        } else {
                            $stepType = 'head_approval';
                            $targetUnitName = null;
                            if ($document->branch_id && $targetUser->relationLoaded('unitKerjas') && $targetUser->unitKerjas->isNotEmpty()) {
                                $matchedUnit = $targetUser->unitKerjas->first(function ($uk) use ($document) {
                                    return $uk->pivot?->branch_id == $document->branch_id || is_null($uk->pivot?->branch_id);
                                });
                                $targetUnitName = $matchedUnit?->nama_unit_kerja;
                            }
                            if (!$targetUnitName) {
                                $targetUnitName = $targetUser->unitKerjas->first()?->nama_unit_kerja
                                    ?? $targetUser->unitKerja?->nama_unit_kerja
                                    ?? $document->unitKerja?->nama_unit_kerja
                                    ?? 'Unit Kerja';
                            }

                            $stepName = __('Persetujuan Kepala Unit :unit (:name)', [
                                'unit' => $targetUnitName,
                                'name' => $targetUser->name,
                            ]);
                        }
                    } elseif (UnitKerja::where('pic_user_id', $targetUser->id)->exists()) {
                        $unit = UnitKerja::where('pic_user_id', $targetUser->id)->first();
                        $stepType = 'pic_unit_acknowledge';
                        $stepName = __('Verifikasi PIC :unit (:name)', [
                            'unit' => $unit->nama_unit_kerja,
                            'name' => $targetUser->name,
                        ]);
                    } else {
                        $stepType = 'peer_review';
                        $stepName = __('Verifikasi / TTD :name', ['name' => $targetUser->name]);
                    }

                    $rawSteps[] = [
                        'weight' => $weight,
                        'signature_request_id' => $sigReq->id,
                        'step_type' => $stepType,
                        'step_name' => $stepName,
                        'assigned_user_id' => $targetUser->id,
                        'assigned_role' => $role,
                        'status' => $isCreator ? 'bypassed' : 'waiting',
                        'action_by_id' => $isCreator ? $creator->id : null,
                        'action_at' => $isCreator ? now() : null,
                        'notes' => $isCreator ? __('Dilewati otomatis (Pembuat dokumen)') : null,
                    ];
                }

                // Sort all steps by weight
                $sortedSteps = collect($rawSteps)->sortBy('weight');

                foreach ($sortedSteps as $stepData) {
                    unset($stepData['weight']);
                    $stepData['step_order'] = $order++;
                    $stepsToCreate[] = $stepData;
                }
            } else {
                // ── Fallback when NO signature boxes are in the document ──
                // Dokumen tanpa kotak TTD hanya memerlukan Persetujuan / Review Kepala Unit Kerja (PIC Unit).
                // Pengesahan PIC Klinik hanya dibuat apabila kotak TTD PIC Klinik disematkan ke dalam dokumen.
                $picUnit = null;
                $docUnitKerjaId = $document->unit_kerja_id ?? $creator->unit_kerja_id ?? ($creator->allUnitKerjaIds()[0] ?? null);
                $originUnit = $document->unitKerja ?? ($docUnitKerjaId ? UnitKerja::find($docUnitKerjaId) : null);
                if ($originUnit) {
                    if ($originUnit->pic_user_id) {
                        $picUnit = $originUnit->picUser;
                    } else {
                        $picUnit = User::where('system_role', 'head')
                            ->where('is_active', true)
                            ->where(function ($q) use ($originUnit) {
                                $q->where('unit_kerja_id', $originUnit->id)
                                  ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $originUnit->id));
                            })
                            ->first();
                    }
                }

                if (!$picUnit) {
                    $picUnit = $this->findActiveUsers($document, 'pic_klinik', $creator)->first()
                        ?? $this->findActiveUsers($document, 'head', $creator)->first()
                        ?? $this->findActiveUsers($document, 'admin', $creator)->first();
                }

                if ($picUnit) {
                    $isCreatorPicUnit = ($creator->id === $picUnit->id);
                    $unitName = $originUnit?->nama_unit_kerja ?? 'Unit Kerja';

                    $stepName = $picUnit->isPicKlinik()
                        ? __('Pengesahan Kepala Cabang :branch (:name)', [
                            'branch' => $document->branch?->name ?? 'Cabang',
                            'name' => $picUnit->name,
                        ])
                        : ($picUnit->isAdmin()
                            ? __('Review & Pengesahan Admin (:name)', ['name' => $picUnit->name])
                            : ($picUnit->isDirector()
                                ? __('Pengesahan Direktur PT (:name)', ['name' => $picUnit->name])
                                : __('Persetujuan Kepala Unit :unit (:name)', [
                                    'unit' => $unitName,
                                    'name' => $picUnit->name,
                                ])));

                    $stepType = $picUnit->isPicKlinik()
                        ? 'pic_klinik_approval'
                        : ($picUnit->isAdmin()
                            ? 'admin_approval'
                            : ($picUnit->isDirector() ? 'director_approval' : 'pic_unit_acknowledge'));

                    $stepsToCreate[] = [
                        'step_order' => $order++,
                        'signature_request_id' => null,
                        'step_type' => $stepType,
                        'step_name' => $stepName,
                        'assigned_user_id' => $picUnit->id,
                        'assigned_role' => $picUnit->system_role ?? 'head',
                        'status' => $isCreatorPicUnit ? 'bypassed' : 'waiting',
                        'action_by_id' => $isCreatorPicUnit ? $creator->id : null,
                        'action_at' => $isCreatorPicUnit ? now() : null,
                        'notes' => $isCreatorPicUnit ? __('Dilewati otomatis (Pembuat dokumen)') : null,
                    ];
                }
            }

            // Persist all steps
            $createdSteps = collect();
            foreach ($stepsToCreate as $data) {
                $createdSteps->push(DocumentApprovalStep::create(array_merge($data, [
                    'document_id' => $document->id,
                    'version_id' => $version->id,
                ])));
            }

            // Ensure signature requests on waiting steps are NOT marked notified until their turn arrives
            foreach ($createdSteps as $st) {
                if ($st->status === 'waiting' && $st->signature_request_id) {
                    SignatureRequest::where('id', $st->signature_request_id)->update(['notified_at' => null]);
                }
            }

            // Find first step that is waiting
            $firstActiveStep = $createdSteps->first(fn($s) => $s->status === 'waiting');

            if ($firstActiveStep) {
                $firstActiveStep->update(['status' => 'pending']);

                $document->update([
                    'approver_id' => $firstActiveStep->assigned_user_id,
                    'approver_role' => $firstActiveStep->assigned_role ?? $firstActiveStep->assignedUser?->system_role,
                ]);

                // Notify active approver / signer with unified DocumentApprovalRequested (prevent duplicate dispatch)
                if ($firstActiveStep->assignedUser) {
                    $hasUnreadNotif = $firstActiveStep->assignedUser->unreadNotifications()
                        ->where('type', DocumentApprovalRequested::class)
                        ->where(function ($q) use ($document, $version) {
                            $q->where('data->version_id', (string) $version->id)
                              ->orWhere('data->version_id', (int) $version->id)
                              ->orWhere(function ($sub) use ($document, $version) {
                                  $sub->where('data->document_id', (string) $document->id)
                                      ->where('data->version_number', (int) $version->version_number);
                              });
                        })
                        ->exists();

                    if (!$hasUnreadNotif) {
                        $firstActiveStep->assignedUser->notify(
                            new DocumentApprovalRequested($document, $version, $creator->name)
                        );
                    }
                }

                // If the first active step is linked to a signature request, mark it notified
                if ($firstActiveStep->signature_request_id) {
                    $firstActiveStep->signatureRequest?->update([
                        'notified_at' => now(),
                        'requested_at' => now(),
                    ]);
                }

                $stepLabel = $firstActiveStep->step_name;
                if ($firstActiveStep->assignedUser && !str_contains($stepLabel, '(' . $firstActiveStep->assignedUser->name . ')') && !str_contains($stepLabel, $firstActiveStep->assignedUser->name)) {
                    $stepLabel .= ' (' . $firstActiveStep->assignedUser->name . ')';
                }

                $message = __(':step akan mereview dokumen Anda.', [
                    'step' => $stepLabel,
                ]);

                $hasCreatorNotif = $creator->unreadNotifications()
                    ->where('type', ApprovalRouteResolved::class)
                    ->where(function ($q) use ($document) {
                        $q->where('data->document_id', (string) $document->id)
                          ->orWhere('data->document_id', (int) $document->id);
                    })
                    ->where('created_at', '>=', now()->subSeconds(30))
                    ->exists();

                if (!$hasCreatorNotif) {
                    $creator->notify(new ApprovalRouteResolved(
                        $document,
                        $firstActiveStep->assigned_role ?? 'head',
                        $firstActiveStep->assignedUser?->name ?? 'Approver',
                        $message,
                        false
                    ));
                }

                return [
                    'steps' => $createdSteps,
                    'activeStep' => $firstActiveStep,
                    'message' => $message,
                ];
            }

            if ($createdSteps->isNotEmpty() && $createdSteps->every(fn($s) => in_array($s->status, ['bypassed', 'approved'], true))) {
                // If ALL steps are bypassed, auto-approve immediately
                app(VersionService::class)->approve($version, $creator, __('Persetujuan otomatis (semua tahap dilewati)'));

                return [
                    'steps' => $createdSteps,
                    'activeStep' => null,
                    'message' => __('Dokumen langsung disetujui otomatis.'),
                ];
            }

            $resolution = $this->resolveApprover($document, $creator);
            $this->applyToDocument($document, $resolution);

            return [
                'steps' => $createdSteps,
                'activeStep' => null,
                'message' => $resolution['message'],
            ];
        });
    }

    /**
     * Alias for compileWorkflowFromSignatures.
     */
    public function initializeWorkflow(Document $document, DocumentVersion $version, User $creator, ?int $selectedPicId = null): array
    {
        return $this->compileWorkflowFromSignatures($document, $version, $creator);
    }

    /**
     * Advance approval step to the next tier or finalize approval.
     */
    public function advanceApproval(
        DocumentApprovalStep $step,
        User $actor,
        ?string $notes = null,
        bool $escalateToKacab = false,
        bool $includeSignature = false,
        ?int $signatureId = null,
        int $pageNumber = 1,
        string $presetPosition = 'bottom-right'
    ): bool {
        return DB::transaction(function () use ($step, $actor, $notes, $escalateToKacab, $includeSignature, $signatureId, $pageNumber, $presetPosition) {
            $step->update([
                'status' => 'approved',
                'action_by_id' => $actor->id,
                'action_at' => now(),
                'notes' => $notes,
            ]);

            $document = $step->document;
            $version = $step->version;

            // Merge with Signature: Automatically apply and stamp any pending signature request specifically assigned to this approver/step
            $sigRequests = collect();

            if ($step->signature_request_id && $step->signatureRequest && $step->signatureRequest->status === 'pending') {
                $sigRequests->push($step->signatureRequest);
            } else {
                $userSigRequests = SignatureRequest::where('document_id', $document->id)
                    ->where('target_user_id', $actor->id)
                    ->where('status', 'pending')
                    ->get();
                $sigRequests = $userSigRequests;
            }

            foreach ($sigRequests as $sigReq) {
                // In workflow progression, the workflow itself notifies author/requester via DocumentApprovalResult upon completion
                $this->applyAndStampSignature($sigReq, $actor, notifyRequester: false);
            }

            // Handle dynamic escalation from PIC Unit to Kepala Cabang if requested
            if ($escalateToKacab && $step->step_type === 'pic_unit_acknowledge') {
                $hasSubsequent = DocumentApprovalStep::where('version_id', $version->id)
                    ->where('step_order', '>', $step->step_order)
                    ->exists();

                if (!$hasSubsequent) {
                    $picKlinik = $document->branch?->picKlinik
                        ?? $this->findActiveUsers($document, 'head')->first()
                        ?? $this->findActiveUsers($document, 'admin')->first();

                    $branchName = $document->branch?->name ?? 'Cabang';

                    DocumentApprovalStep::create([
                        'document_id' => $document->id,
                        'version_id' => $version->id,
                        'step_order' => $step->step_order + 1,
                        'step_type' => 'pic_klinik_approval',
                        'step_name' => __('Pengesahan Kepala Cabang :branch (Eskalasi)', ['branch' => $branchName]),
                        'assigned_user_id' => $picKlinik?->id,
                        'assigned_role' => 'head',
                        'status' => 'waiting',
                    ]);
                }
            }

            // Find next waiting step for this version
            $nextStep = DocumentApprovalStep::where('version_id', $version->id)
                ->where('step_order', '>', $step->step_order)
                ->where('status', 'waiting')
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {
                $nextStep->update(['status' => 'pending']);

                $document->update([
                    'approver_id' => $nextStep->assigned_user_id,
                    'approver_role' => $nextStep->assigned_role ?? $nextStep->assignedUser?->system_role,
                ]);

                if ($nextStep->assignedUser) {
                    $authorName = $version->author?->name ?? 'User';
                    $hasUnreadNotif = $nextStep->assignedUser->unreadNotifications()
                        ->where('type', DocumentApprovalRequested::class)
                        ->where(function ($q) use ($document, $version) {
                            $q->where('data->version_id', (string) $version->id)
                              ->orWhere('data->version_id', (int) $version->id)
                              ->orWhere(function ($sub) use ($document, $version) {
                                  $sub->where('data->document_id', (string) $document->id)
                                      ->where('data->version_number', (int) $version->version_number);
                              });
                        })
                        ->exists();

                    if (!$hasUnreadNotif) {
                        $nextStep->assignedUser->notify(
                            new DocumentApprovalRequested($document, $version, $authorName)
                        );
                    }
                }

                // If next step is linked to a signature request, mark it notified
                if ($nextStep->signature_request_id) {
                    $nextStep->signatureRequest?->update([
                        'notified_at' => now(),
                        'requested_at' => now(),
                    ]);
                }

                return false; // Workflow still has pending steps
            }

            // ── All steps completed -> Finalize version approval ──
            app(VersionService::class)->approve($version, $actor, $notes);

            $document->update([
                'approver_id' => null,
                'approver_role' => null,
            ]);

            // ── Director Rule: If no Director signature was in the workflow, notify Director as Tembusan (Only To Know) ──
            $hasDirectorStep = DocumentApprovalStep::where('version_id', $version->id)
                ->where('assigned_role', 'direktur')
                ->whereIn('status', ['approved', 'bypassed'])
                ->exists();

            if (!$hasDirectorStep) {
                $directors = $this->findActiveUsers($document, 'direktur');
                if ($directors->isNotEmpty()) {
                    $document->update(['director_notified_at' => now()]);

                    foreach ($directors as $director) {
                        $director->notify(new DirectorDocumentTembusanNotification($document, $version, $actor->name));
                    }
                }
            }

            if ($version->author) {
                $version->author->notify(new DocumentApprovalResult($document, $version, 'approved', $actor->name, $notes));
            }

            return true; // Workflow finalized
        });
    }

    /**
     * Apply and stamp user signature/stamp directly onto the document and mark SignatureRequest approved.
     */
    public function applyAndStampSignature(SignatureRequest $signatureRequest, ?User $actor = null, bool $notifyRequester = true): void
    {
        $actor = $actor ?? auth()->user();

        $signatureRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        $document = $signatureRequest->document;
        $version = $document?->displayVersion();
        $targetUser = $signatureRequest->targetUser ?? $actor;

        if ($document && $version && $targetUser) {
            $requestId = $signatureRequest->id;

            $sig = null;
            if ($signatureRequest->isStamp() && $signatureRequest->requestedSignature) {
                $sig = $signatureRequest->requestedSignature;
            } elseif ($signatureRequest->requestedSignature) {
                $sig = $signatureRequest->requestedSignature;
            } else {
                $sig = $targetUser->signatures()->where('type', 'original')->first()
                    ?? $targetUser->signatures()->first();
            }

            $signaturePath = null;
            if ($sig && $sig->file_path) {
                if (Storage::disk('public')->exists($sig->file_path)) {
                    $signaturePath = Storage::disk('public')->path($sig->file_path);
                } elseif (file_exists(storage_path('app/public/' . ltrim($sig->file_path, '/')))) {
                    $signaturePath = storage_path('app/public/' . ltrim($sig->file_path, '/'));
                } elseif (file_exists(public_path('storage/' . ltrim($sig->file_path, '/')))) {
                    $signaturePath = public_path('storage/' . ltrim($sig->file_path, '/'));
                } elseif (file_exists($sig->file_path)) {
                    $signaturePath = $sig->file_path;
                }
            }

            if ($signaturePath && file_exists($signaturePath)) {
                if ($version->file_path) {
                    $processor = app(DocumentProcessorService::class);
                    $processor->processSignature($document, $version, $requestId, $signaturePath, $signatureRequest);
                    app(OnlyOfficeService::class)->rotateDocumentKey($document, $version);
                } else {
                    // For HTML-based documents without placeholder tag, append signature tag if not present
                    if ($version->content && !str_contains($version->content, "[ttd:{$targetUser->name}]") && !str_contains($version->content, "[stamp:{$targetUser->name}]")) {
                        $version->update([
                            'content' => $version->content . '<p class="mt-4"><br></p><p><strong>Disetujui oleh:</strong></p><p>[ttd:' . $targetUser->name . ']</p>',
                        ]);
                    }
                }
            }
        }

        $signatureRequest->loadMissing(['requester', 'document', 'targetUser', 'requestedSignature.company']);
        if ($notifyRequester && $signatureRequest->requester && $signatureRequest->document && $signatureRequest->requester_id !== $actor?->id) {
            $signatureRequest->requester->notify(
                new \App\Notifications\SignatureRequestApprovedNotification(
                    $signatureRequest,
                    $signatureRequest->document,
                    $signatureRequest->targetUser?->name ?? $actor?->name ?? 'User'
                )
            );
        }
    }

    /**
     * Reject document at current approval step and cancel subsequent steps.
     */
    public function rejectApproval(DocumentApprovalStep $step, User $actor, string $reason): void
    {
        DB::transaction(function () use ($step, $actor, $reason) {
            $step->update([
                'status' => 'rejected',
                'action_by_id' => $actor->id,
                'action_at' => now(),
                'notes' => $reason,
            ]);

            $document = $step->document;
            $version = $step->version;

            $sigRequests = SignatureRequest::where('document_id', $document->id)
                ->where('target_user_id', $actor->id)
                ->where('status', 'pending')
                ->get();

            if ($step->signature_request_id && $step->signatureRequest && $step->signatureRequest->status === 'pending') {
                if (!$sigRequests->contains(fn($r) => $r->id === $step->signature_request_id)) {
                    $sigRequests->push($step->signatureRequest);
                }
            }

            foreach ($sigRequests as $sigReq) {
                $sigReq->update([
                    'status' => 'rejected',
                    'rejected_reason' => $reason,
                    'responded_at' => now(),
                ]);

                if ($document && $version) {
                    app(OnlyOfficeService::class)->rotateDocumentKey($document, $version);
                }

                $sigReq->loadMissing(['requester', 'document', 'targetUser']);
                if ($sigReq->requester && $sigReq->document && $sigReq->requester_id !== $actor->id) {
                    $sigReq->requester->notify(
                        new \App\Notifications\SignatureRequestRejectedNotification(
                            $sigReq,
                            $sigReq->document,
                            $sigReq->targetUser?->name ?? $actor->name,
                            $reason
                        )
                    );
                }
            }

            // Cancel any remaining waiting steps
            DocumentApprovalStep::where('version_id', $step->version_id)
                ->where('status', 'waiting')
                ->update(['status' => 'cancelled']);

            app(VersionService::class)->reject($version, $actor, $reason);

            $document->update([
                'approver_id' => null,
                'approver_role' => null,
            ]);

            if ($version->author) {
                $version->author->notify(new DocumentApprovalResult($document, $version, 'rejected', $actor->name, $reason));
            }
        });
    }

    // ───────────────────────────────────────────────────
    // Helper Finders & Query Builders
    // ───────────────────────────────────────────────────

    public function findHeadForDocument(Document $document, ?User $excludeUser = null): ?User
    {
        $heads = $this->findActiveUsers($document, 'head', $excludeUser);
        return $heads->first() ?? $this->findActiveUsers($document, 'admin', $excludeUser)->first();
    }

    public function findDirectorForDocument(Document $document, ?User $excludeUser = null): ?User
    {
        $directors = $this->findActiveUsers($document, 'direktur', $excludeUser);
        return $directors->first() ?? User::where('system_role', 'direktur')->where('is_active', true)->first();
    }

    /**
     * Find active users with the given role in the same branch/PT context as the document.
     */
    public function findActiveUsers(Document $document, string $role, ?User $excludeUser = null): Collection
    {
        $query = User::where('system_role', $role)
            ->where('is_active', true);

        if ($excludeUser) {
            $query->where('id', '!=', $excludeUser->id);
        }

        if ($role === 'pic_klinik') {
            if ($document->branch_id) {
                $query->whereHas('branches', fn($bq) => $bq->where('branches.id', $document->branch_id));
            } elseif ($document->company_id) {
                $query->whereHas('companies', fn($cq) => $cq->where('companies.id', $document->company_id));
            }
        } elseif ($role === 'head') {
            if ($document->unit_kerja_id) {
                $query->where(function ($q) use ($document) {
                    $q->where('unit_kerja_id', $document->unit_kerja_id)
                      ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $document->unit_kerja_id));
                });
            }

            if ($document->branch_id) {
                $query->where(function ($q) use ($document) {
                    $q->whereHas('branches', fn($bq) => $bq->where('branches.id', $document->branch_id));
                    if ($document->company_id) {
                        $q->orWhere(function ($cq) use ($document) {
                            $cq->whereHas('companies', fn($c) => $c->where('companies.id', $document->company_id))
                               ->whereDoesntHave('branches');
                        });
                    }
                });
            } elseif ($document->company_id) {
                $query->whereHas('companies', fn($cq) => $cq->where('companies.id', $document->company_id));
            }
        } else {
            if ($role === 'direktur') {
                $companyId = $document->company_id ?? $document->branch?->company_id;
                if ($companyId) {
                    $query->whereHas('companies', fn($cq) => $cq->where('companies.id', $companyId));
                }
            }
        }

        return $query->get();
    }

    /**
     * Resolve approver(s) for the given document.
     */
    public function resolveApprover(Document $document, ?User $excludeUser = null): array
    {
        $currentStep = $document->currentApprovalStep();
        if ($currentStep && $currentStep->assignedUser) {
            return [
                'approvers' => collect([$currentStep->assignedUser]),
                'role' => $currentStep->assigned_role ?? $currentStep->assignedUser->system_role,
                'message' => __(':approver akan mereview dokumen Anda.', ['approver' => $currentStep->assignedUser->name]),
                'isFallback' => false,
            ];
        }

        $picKliniks = $this->findActiveUsers($document, 'pic_klinik', $excludeUser);
        if ($picKliniks->isNotEmpty()) {
            return [
                'approvers' => $picKliniks,
                'role' => 'pic_klinik',
                'message' => __(':approver (Kepala Cabang) akan mereview dokumen Anda.', ['approver' => $picKliniks->pluck('name')->join(', ')]),
                'isFallback' => false,
            ];
        }

        $heads = $this->findActiveUsers($document, 'head', $excludeUser);
        if ($heads->isNotEmpty()) {
            return [
                'approvers' => $heads,
                'role' => 'head',
                'message' => __(':approver (Head) akan mereview dokumen Anda.', ['approver' => $heads->pluck('name')->join(', ')]),
                'isFallback' => false,
            ];
        }

        $admins = $this->findActiveUsers($document, 'admin', $excludeUser);
        if ($admins->isNotEmpty()) {
            return [
                'approvers' => $admins,
                'role' => 'admin',
                'message' => __('Dokumen akan di-review oleh :approver (Admin).', ['approver' => $admins->pluck('name')->join(', ')]),
                'isFallback' => true,
            ];
        }

        $direkturs = $this->findActiveUsers($document, 'direktur', $excludeUser);
        if ($direkturs->isNotEmpty()) {
            return [
                'approvers' => $direkturs,
                'role' => 'direktur',
                'message' => __('Dokumen akan di-review oleh :approver (Direktur).', ['approver' => $direkturs->pluck('name')->join(', ')]),
                'isFallback' => true,
            ];
        }

        return [
            'approvers' => collect(),
            'role' => null,
            'message' => __('Approver tidak ditemukan. Silakan hubungi Super Admin.'),
            'isFallback' => true,
        ];
    }

    public function applyToDocument(Document $document, array $resolution): void
    {
        $firstApprover = $resolution['approvers']->first();
        $document->update([
            'approver_id' => $firstApprover?->id,
            'approver_role' => $resolution['role'],
        ]);
    }

    /**
     * Resolve all authorized approvers to notify when a document rollback is requested.
     */
    public function resolveRollbackApprovers(Document $document, ?User $excludeUser = null): Collection
    {
        $approvers = collect();

        // 1. Origin Unit Head (Kepala Unit Kerja)
        if ($document->unit_kerja_id) {
            $originUnit = $document->unitKerja ?? UnitKerja::find($document->unit_kerja_id);
            if ($originUnit) {
                if ($originUnit->pic_user_id) {
                    $unitPic = $originUnit->picUser;
                    if ($unitPic && $unitPic->is_active && (!$excludeUser || $unitPic->id !== $excludeUser->id)) {
                        $approvers->push($unitPic);
                    }
                }
                $heads = $this->findActiveUsers($document, 'head', $excludeUser);
                foreach ($heads as $h) {
                    $approvers->push($h);
                }
            }
        }

        // 2. PIC Klinik (Kepala Cabang)
        if ($document->branch_id) {
            $picKlinik = $document->branch?->picKlinik;
            if ($picKlinik && $picKlinik->is_active && (!$excludeUser || $picKlinik->id !== $excludeUser->id)) {
                $approvers->push($picKlinik);
            }
            $picKliniks = $this->findActiveUsers($document, 'pic_klinik', $excludeUser);
            foreach ($picKliniks as $pk) {
                $approvers->push($pk);
            }
        }

        // 3. Fallback to Admins / Directors if no specific unit or branch head found
        if ($approvers->isEmpty()) {
            $admins = $this->findActiveUsers($document, 'admin', $excludeUser);
            foreach ($admins as $adm) {
                $approvers->push($adm);
            }
            $directors = $this->findActiveUsers($document, 'direktur', $excludeUser);
            foreach ($directors as $dir) {
                $approvers->push($dir);
            }
        }

        return $approvers->unique('id');
    }
}
