<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentUnitKerjaShare;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DocumentShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentShareController extends Controller
{
    public function __construct(
        protected DocumentShareService $shareService,
        protected AuditService $auditService,
    ) {}

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $validated = $request->validate([
            'type' => 'required|in:user,unit_kerja,division',
            'user_id' => 'required_without_all:unit_kerja_id,division_id|exists:users,id',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'division_id' => 'nullable|exists:unit_kerjas,id', // backward compatibility fallback
            'role' => 'required|in:editor,viewer',
        ]);

        $invitedBy = auth()->user();

        if ($validated['type'] === 'user') {
            $targetUser = User::findOrFail($validated['user_id']);
            $share = $this->shareService->addUserShare(
                $document,
                $targetUser,
                $validated['role'],
                $invitedBy,
            );
            $this->auditService->log($invitedBy, 'share.user.added', 'document_share', $share->id, [
                'document_id' => $document->id,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'],
            ]);
        } else {
            $ukId = $validated['unit_kerja_id'] ?? $validated['division_id'];
            $unitKerja = UnitKerja::findOrFail($ukId);
            $share = $this->shareService->addUnitKerjaShare(
                $document,
                $unitKerja,
                $validated['role'],
                $invitedBy,
            );
            $this->auditService->log($invitedBy, 'share.unit_kerja.added', 'document_unit_kerja_share', $share->id, [
                'document_id' => $document->id,
                'unit_kerja_id' => $ukId,
                'role' => $validated['role'],
            ]);
        }

        return back()->with('notice', __('Akses berhasil ditambahkan.'));
    }

    public function updateUserShare(Request $request, Document $document, DocumentShare $share): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $validated = $request->validate(['role' => 'required|in:editor,viewer']);

        $this->shareService->updateUserShareRole($share, $validated['role']);
        $this->auditService->log(auth()->user(), 'share.user.updated', 'document_share', $share->id, [
            'document_id' => $document->id,
            'role' => $validated['role'],
        ]);

        if ($share->user_id && $share->user_id !== auth()->id()) {
            $share->user?->notify(new \App\Notifications\DocumentSharedWithUser($document, $validated['role'], auth()->user()->name));
        }

        return back()->with('notice', __('Peran pengguna diperbarui.'));
    }

    public function destroyUserShare(Document $document, DocumentShare $share): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $this->shareService->removeUserShare($share, auth()->user());
        $this->auditService->log(auth()->user(), 'share.user.removed', 'document_share', $share->id, [
            'document_id' => $document->id,
        ]);

        return back()->with('notice', __('Akses pengguna dihapus.'));
    }

    public function updateUnitKerjaShare(Request $request, Document $document, DocumentUnitKerjaShare $unitKerjaShare): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $validated = $request->validate(['role' => 'required|in:editor,viewer']);

        $this->shareService->updateUnitKerjaShareRole($unitKerjaShare, $validated['role']);
        $this->auditService->log(auth()->user(), 'share.unit_kerja.updated', 'document_unit_kerja_share', $unitKerjaShare->id, [
            'document_id' => $document->id,
            'role' => $validated['role'],
        ]);

        $unitKerja = $unitKerjaShare->unitKerja;
        if ($unitKerja) {
            $unitUsers = User::where(function ($q) use ($unitKerja) {
                    $q->where('unit_kerja_id', $unitKerja->id)
                      ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $unitKerja->id));
                })
                ->where('is_active', true)
                ->where('id', '!=', auth()->id())
                ->get();

            foreach ($unitUsers as $member) {
                $member->notify(new \App\Notifications\DocumentSharedWithUnitKerja(
                    $document,
                    $unitKerja->nama_unit_kerja,
                    $validated['role'],
                    auth()->user()->name
                ));
            }
        }

        return back()->with('notice', __('Peran unit kerja diperbarui.'));
    }

    public function destroyUnitKerjaShare(Document $document, DocumentUnitKerjaShare $unitKerjaShare): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $this->shareService->removeUnitKerjaShare($unitKerjaShare, auth()->user());
        $this->auditService->log(auth()->user(), 'share.unit_kerja.removed', 'document_unit_kerja_share', $unitKerjaShare->id, [
            'document_id' => $document->id,
        ]);

        return back()->with('notice', __('Akses unit kerja dihapus.'));
    }

    public function updateGeneralAccess(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $validated = $request->validate([
            'general_access' => 'required|in:restricted,anyone_with_link',
            'link_role' => 'nullable|in:viewer,editor',
        ]);

        $this->shareService->updateGeneralAccess($document, $validated['general_access'], $validated['link_role'] ?? null);
        $this->auditService->log(auth()->user(), 'share.general_access.updated', 'document', $document->id, [
            'general_access' => $validated['general_access'],
            'link_role' => $document->fresh()->link_role,
        ]);

        return back()->with('notice', __('Pengaturan akses umum diperbarui.'));
    }

    public function regenerateToken(Document $document): JsonResponse
    {
        $this->authorize('manageAccess', $document);

        $token = $this->shareService->regenerateShareToken($document);
        $this->auditService->log(auth()->user(), 'share.token.regenerated', 'document', $document->id, []);

        return response()->json([
            'success' => true,
            'share_token' => $token,
            'share_url' => route('documents.shared', $token),
        ]);
    }

    public function shareData(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        if (empty($document->share_token)) {
            $document->update(['share_token' => \Illuminate\Support\Str::random(32)]);
            $document->refresh();
        }

        $document->load(['shares.user', 'unitKerjaShares.unitKerja']);

        return response()->json([
            'owner' => [
                'id' => $document->owner_id,
                'name' => $document->owner?->name,
                'avatar_url' => $document->owner?->avatar_url,
            ],
            'general_access' => $document->general_access ?? 'restricted',
            'link_role' => $document->link_role,
            'share_token' => $document->share_token,
            'share_url' => $document->share_token ? route('documents.shared', $document->share_token) : null,
            'shares' => $document->shares->map(fn(DocumentShare $s) => [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $s->user?->name,
                'email' => $s->user?->email,
                'avatar_url' => $s->user?->avatar_url,
                'role' => $s->role,
            ]),
            'unit_kerja_shares' => $document->unitKerjaShares->map(fn(DocumentUnitKerjaShare $s) => [
                'id' => $s->id,
                'unit_kerja_id' => $s->unit_kerja_id,
                'name' => $s->unitKerja?->nama_unit_kerja,
                'code' => $s->unitKerja?->kode_unit_kerja,
                'role' => $s->role,
            ]),
            'division_shares' => $document->unitKerjaShares->map(fn(DocumentUnitKerjaShare $s) => [
                'id' => $s->id,
                'division_id' => $s->unit_kerja_id,
                'name' => $s->unitKerja?->nama_unit_kerja,
                'role' => $s->role,
            ]),
        ]);
    }

    public function searchSharees(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));

        $users = User::query()
            ->where('is_active', true)
            ->when($term !== '', fn($q) => $q->where(fn($q2) => $q2
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->limit(10)
            ->get()
            ->map(fn(User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar_url,
            ]);

        $unitKerjas = UnitKerja::query()
            ->when($term !== '', fn($q) => $q->where('nama_unit_kerja', 'like', "%{$term}%")->orWhere('kode_unit_kerja', 'like', "%{$term}%"))
            ->orderBy('kode_unit_kerja')
            ->limit(10)
            ->get(['id', 'kode_unit_kerja', 'nama_unit_kerja'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->nama_unit_kerja,
                'code' => $u->kode_unit_kerja,
            ]);

        return response()->json([
            'users' => $users,
            'unit_kerjas' => $unitKerjas,
            'divisions' => $unitKerjas, // backward compatibility for frontend
        ]);
    }

    /**
     * Open a document via its share_token link.
     */
    public function accessByToken(string $token, \App\Services\OnlyOfficeService $onlyOfficeService)
    {
        $document = Document::where('share_token', $token)->firstOrFail();

        $currentUser = auth()->user();

        if (!$currentUser) {
            if ($document->general_access !== DocumentShareService::GENERAL_ACCESS_ANYONE_WITH_LINK) {
                return redirect()->guest(route('login'));
            }

            $document->load('owner', 'unitKerja', 'documentType', 'currentVersion', 'versions.author');
            $version = $document->displayVersion();
            $onlyOfficeConfig = null;
            $ext = strtolower(pathinfo($version?->file_path ?? '', PATHINFO_EXTENSION));
            if ($version && $version->file_path && in_array($ext, ['doc', 'docx'], true)) {
                $mode = ($document->link_role === 'editor') ? 'edit' : 'view';
                $onlyOfficeConfig = $onlyOfficeService->generateEditorConfig(
                    $document,
                    $version,
                    null,
                    $mode
                );
            }

            return view('documents.shared_public', compact('document', 'version', 'onlyOfficeConfig', 'token'));
        }

        $this->authorize('view', $document);

        if ($currentUser) {
            $currentUser->unreadNotifications()
                ->where('data->type', 'document_shared')
                ->where('data->document_id', $document->id)
                ->update(['read_at' => now()]);
        }

        if ($document->owner_id && $currentUser && $currentUser->id !== $document->owner_id) {
            $throttleKey = 'notif_doc_opened_link_' . $document->id . '_' . $currentUser->id;
            if (\Illuminate\Support\Facades\Cache::add($throttleKey, true, now()->addMinutes(15))) {
                $document->owner?->notify(new \App\Notifications\DocumentOpenedViaLink($document, $currentUser->name));
            }
        }

        $document->load('owner', 'unitKerja', 'documentType', 'currentVersion', 'versions.author', 'shares.user', 'unitKerjaShares.unitKerja');

        $unitKerjas = auth()->user()->isAdmin()
            ? UnitKerja::orderBy('kode_unit_kerja')->get()
            : UnitKerja::whereIn('id', auth()->user()->allUnitKerjaIds())->get();

        $version = $document->displayVersion();
        $onlyOfficeConfig = null;
        $ext = strtolower(pathinfo($version?->file_path ?? '', PATHINFO_EXTENSION));
        if ($version && $version->file_path && in_array($ext, ['doc', 'docx'], true)) {
            $onlyOfficeConfig = $onlyOfficeService->generateEditorConfig(
                $document,
                $version,
                auth()->user(),
                'view'
            );
        }
        $companies = \App\Models\Company::with('branches')->get();

        $approvedSignatures = \App\Models\SignatureRequest::where('document_id', $document->id)
            ->where('status', 'approved')
            ->where('is_used', false)
            ->with(['targetUser.signatures', 'requestedSignature.company'])
            ->get()
            ->map(function ($req) use ($onlyOfficeService) {
                $targetUser = $req->targetUser;
                if (!$targetUser || !$targetUser->hasSignature()) {
                    return null;
                }
                $sig = $req->requestedSignature 
                    ?? $targetUser->signatures()->where('type', 'original')->first() 
                    ?? $targetUser->signatures()->first();
                if (!$sig) {
                    return null;
                }
                return [
                    'request_id' => $req->id,
                    'url' => $onlyOfficeService->getSignatureFileUrlForSignature($sig),
                    'target_user_name' => $targetUser->name,
                    'type' => $sig->type,
                    'company_name' => $sig->company?->name,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return view('documents.show', compact('document', 'unitKerjas', 'onlyOfficeConfig', 'version', 'approvedSignatures', 'companies'));
    }

    /**
     * Serve raw document file via public share token (for fallback iframe/mammoth preview).
     */
    public function fileByToken(string $token)
    {
        $document = Document::where('share_token', $token)->firstOrFail();

        if (!auth()->check() && $document->general_access !== DocumentShareService::GENERAL_ACCESS_ANYONE_WITH_LINK) {
            abort(403);
        }
        if (auth()->check()) {
            $this->authorize('view', $document);
        }

        $version = $document->displayVersion();
        abort_unless($version && $version->file_path, 404);

        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
        abort_unless($disk->exists($version->file_path), 404);

        $ext = strtolower(pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'docx');
        $effectiveName = ($document->title ?: 'document') . '.' . $ext;
        $bytes = $disk->get($version->file_path);

        return response($bytes, 200, [
            'Content-Type' => $ext === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Length' => strlen($bytes),
            'Content-Disposition' => 'inline; filename="' . $effectiveName . '"',
            'Accept-Ranges' => 'none',
        ]);
    }

    /**
     * Download DOCX/PDF file via public share token.
     */
    public function downloadByToken(Request $request, string $token)
    {
        $document = Document::where('share_token', $token)->firstOrFail();

        if (!auth()->check() && $document->general_access !== DocumentShareService::GENERAL_ACCESS_ANYONE_WITH_LINK) {
            return redirect()->guest(route('login'));
        }
        if (auth()->check()) {
            $this->authorize('view', $document);
        }

        $version = $document->displayVersion();
        abort_unless($version && $version->file_path, 404, 'File not found');

        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
        abort_unless($disk->exists($version->file_path), 404, 'Physical file not found');

        $withKop = $request->boolean('with_kop', true);
        $fileBytes = $disk->get($version->file_path);

        $downloadName = $version->file_original_name ?? $document->title;
        $ext = strtolower(pathinfo($downloadName, PATHINFO_EXTENSION) ?: (pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'docx'));
        $baseName = pathinfo($downloadName, PATHINFO_FILENAME);

        if (!$withKop && $ext === 'docx') {
            $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
            $fileBytes = $onlyOfficeService->removeHeaderAndFooterFromDocx($fileBytes);
            $downloadName = $baseName . ' (Tanpa Kop).' . $ext;
        } else {
            if (!str_ends_with(strtolower($downloadName), '.docx') && !str_ends_with(strtolower($downloadName), '.pdf')) {
                $downloadName .= '.' . $ext;
            }
        }

        return response($fileBytes, 200, [
            'Content-Type' => $ext === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Length' => strlen($fileBytes),
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'Accept-Ranges' => 'none',
        ]);
    }

    /**
     * Export & download PDF via public share token.
     */
    public function exportPdfByToken(Request $request, string $token, \App\Services\PdfExportService $pdfService)
    {
        $document = Document::where('share_token', $token)->firstOrFail();

        if (!auth()->check() && $document->general_access !== DocumentShareService::GENERAL_ACCESS_ANYONE_WITH_LINK) {
            return redirect()->guest(route('login'));
        }
        if (auth()->check()) {
            $this->authorize('view', $document);
        }

        $validated = $request->validate([
            'paper_size' => 'nullable|string|in:F4,A4,A5,A3,Letter,Legal,Custom',
            'custom_width' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
            'custom_height' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
            'custom_unit' => 'nullable|string|in:cm,mm',
            'with_kop' => 'nullable|boolean',
        ]);

        $paperSize = $validated['paper_size'] ?? $document->paper_size ?? 'A4';
        $withKop = $request->boolean('with_kop', true);
        $customDimensions = null;
        if ($paperSize === 'Custom') {
            $customDimensions = [
                'width' => (float) $validated['custom_width'],
                'height' => (float) $validated['custom_height'],
                'unit' => $validated['custom_unit'] ?? 'cm',
            ];
        }

        $result = $pdfService->export($document, auth()->user(), $paperSize, $customDimensions, $withKop);

        $fullPath = storage_path('app/private/' . $result['path']);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $result['path']);
        }

        abort_unless(file_exists($fullPath), 404, 'PDF file not found');

        $pdfContent = file_get_contents($fullPath);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => strlen($pdfContent),
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            'Accept-Ranges' => 'none',
            'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
        ]);
    }
}