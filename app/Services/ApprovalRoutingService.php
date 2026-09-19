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

            // Fetch signature requests attached to this document
            $signatureRequests = SignatureRequest::where('document_id', $document->id)
                ->with(['targetUser', 'requestedSignature'])
                ->get();

            $stepsToCreate = [];
            $order = 1;

            if ($signatureRequests->isNotEmpty()) {
                // Sort signature requests by hierarchical weight (Staff -> PIC Unit -> Head -> Director -> Admin)
                $sortedRequests = $signatureRequests->sortBy(fn(SignatureRequest $sr) => $sr->role_weight);

                foreach ($sortedRequests as $sigReq) {
                    $targetUser = $sigReq->targetUser;
                    if (!$targetUser) {
                        continue;
                    }

                    $isCreator = ($creator->id === $targetUser->id);
                    $role = $targetUser->system_role;

                    // Determine step type & human-readable name
                    if ($targetUser->isDirector()) {
                        $stepType = 'director_approval';
                        $stepName = __('Pengesahan Direktur PT (:name)', ['name' => $targetUser->name]);
                    } elseif ($targetUser->isHead()) {
                        if ($document->branch && $document->branch->pic_klinik_id === $targetUser->id) {
                            $stepType = 'pic_klinik_approval';
                            $stepName = __('Pengesahan Kepala Cabang :branch (:name)', [
                                'branch' => $document->branch->name,
                                'name' => $targetUser->name,
                            ]);
                        } else {
                            $stepType = 'head_approval';
                            $stepName = __('Persetujuan Kepala Unit :unit (:name)', [
                                'unit' => $document->unitKerja?->nama_unit_kerja ?? 'Unit Kerja',
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

                    $stepsToCreate[] = [
                        'step_order' => $order++,
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
            } else {
                // ── Fallback when NO signature boxes are in the document ──
                $picUnit = null;
                if ($document->unitKerja?->pic_user_id) {
                    $picUnit = $document->unitKerja->picUser;
                } else {
                    $picUnit = $this->findActiveUsers($document, 'head')->first();
                }

                if ($picUnit) {
                    $isCreatorPicUnit = ($creator->id === $picUnit->id);
                    $unitName = $document->unitKerja?->nama_unit_kerja ?? 'Unit Kerja';

                    $stepsToCreate[] = [
                        'step_order' => $order++,
                        'signature_request_id' => null,
                        'step_type' => 'pic_unit_acknowledge',
                        'step_name' => __('Verifikasi PIC :unit', ['unit' => $unitName]),
                        'assigned_user_id' => $picUnit->id,
                        'assigned_role' => 'head',
                        'status' => $isCreatorPicUnit ? 'bypassed' : 'waiting',
                        'action_by_id' => $isCreatorPicUnit ? $creator->id : null,
                        'action_at' => $isCreatorPicUnit ? now() : null,
                        'notes' => $isCreatorPicUnit ? __('Dilewati otomatis (Pembuat adalah PIC Unit Kerja)') : null,
                    ];
                }

                $picKlinik = $document->branch?->picKlinik
                    ?? ($isPusat ? null : $this->findActiveUsers($document, 'head')->first())
                    ?? $this->findActiveUsers($document, 'admin')->first();

                if ($picKlinik && (!$picUnit || $picKlinik->id !== $picUnit->id)) {
                    $isCreatorPicKlinik = ($creator->id === $picKlinik->id);
                    $branchName = $document->branch?->name ?? 'Cabang';

                    $stepsToCreate[] = [
                        'step_order' => $order++,
                        'signature_request_id' => null,
                        'step_type' => 'pic_klinik_approval',
                        'step_name' => __('Pengesahan Kepala :branch', ['branch' => $branchName]),
                        'assigned_user_id' => $picKlinik->id,
                        'assigned_role' => 'head',
                        'status' => $isCreatorPicKlinik ? 'bypassed' : 'waiting',
                        'action_by_id' => $isCreatorPicKlinik ? $creator->id : null,
                        'action_at' => $isCreatorPicKlinik ? now() : null,
                        'notes' => $isCreatorPicKlinik ? __('Dilewati otomatis (Pembuat adalah Kepala Cabang)') : null,
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

            // Find first step that is waiting
            $firstActiveStep = $createdSteps->first(fn($s) => $s->status === 'waiting');

            if ($firstActiveStep) {
                $firstActiveStep->update(['status' => 'pending']);

                $document->update([
                    'approver_id' => $firstActiveStep->assigned_user_id,
                    'approver_role' => $firstActiveStep->assigned_role ?? $firstActiveStep->assignedUser?->system_role,
                ]);

                // Notify active approver
                if ($firstActiveStep->assignedUser) {
                    $firstActiveStep->assignedUser->notify(
                        new DocumentApprovalRequested($document, $version, $creator->name)
                    );
                }

                $message = __(':step akan mereview dokumen Anda.', [
                    'step' => $firstActiveStep->step_name . ($firstActiveStep->assignedUser ? ' (' . $firstActiveStep->assignedUser->name . ')' : ''),
                ]);

                $creator->notify(new ApprovalRouteResolved(
                    $document,
                    $firstActiveStep->assigned_role ?? 'head',
                    $firstActiveStep->assignedUser?->name ?? 'Approver',
                    $message,
                    false
                ));

                return [
                    'steps' => $createdSteps,
                    'activeStep' => $firstActiveStep,
                    'message' => $message,
                ];
            }

            // If ALL steps are bypassed, auto-approve immediately
            app(VersionService::class)->approve($version, $creator, __('Persetujuan otomatis (semua tahap dilewati)'));

            return [
                'steps' => $createdSteps,
                'activeStep' => null,
                'message' => __('Dokumen langsung disetujui otomatis.'),
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
    public function advanceApproval(DocumentApprovalStep $step, User $actor, ?string $notes = null, bool $escalateToKacab = false): bool
    {
        return DB::transaction(function () use ($step, $actor, $notes, $escalateToKacab) {
            $step->update([
                'status' => 'approved',
                'action_by_id' => $actor->id,
                'action_at' => now(),
                'notes' => $notes,
            ]);

            // If step is linked to a SignatureRequest, mark it approved
            if ($step->signature_request_id && $step->signatureRequest) {
                $step->signatureRequest->update([
                    'status' => 'approved',
                    'responded_at' => now(),
                ]);
            }

            $document = $step->document;
            $version = $step->version;

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
                    $nextStep->assignedUser->notify(
                        new DocumentApprovalRequested($document, $version, $authorName)
                    );
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

            if ($step->signature_request_id && $step->signatureRequest) {
                $step->signatureRequest->update([
                    'status' => 'rejected',
                    'rejected_reason' => $reason,
                    'responded_at' => now(),
                ]);
            }

            // Cancel any remaining waiting steps
            DocumentApprovalStep::where('version_id', $step->version_id)
                ->where('status', 'waiting')
                ->update(['status' => 'cancelled']);

            $document = $step->document;
            $version = $step->version;

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

        if ($role === 'head') {
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
}
