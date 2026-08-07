<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessLogicException;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Services\AccessLinkService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareLinkController extends Controller
{
    public function __construct(
        protected AccessLinkService $accessLinkService,
        protected AuditService $auditService,
    ) {}

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'role' => 'required|in:viewer,editor',
            'expires_at' => 'nullable|date|after:today',
        ]);

        try {
            $link = $this->accessLinkService->create(
                $document,
                $validated['role'],
                $validated['expires_at'] ?? null,
                auth()->user()
            );
        } catch (BusinessLogicException $e) {
            // Same-role active link already exists → hand the existing one back.
            $existing = DocumentAccessLink::query()
                ->activeForRole($document->id, $validated['role'])
                ->first();

            if ($existing) {
                return back()->with('share_link', route('shared.documents', $existing->token))
                    ->with('notice', $e->getMessage());
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $this->auditService->log(auth()->user(), 'link.created', 'document_access_link', $link->id, [
            'document_id' => $document->id,
            'role' => $link->role,
        ]);

        return back()->with('share_link', route('shared.documents', $link->token));
    }

    public function destroy(Document $document, DocumentAccessLink $link): RedirectResponse
    {
        $this->authorize('update', $document);

        $this->accessLinkService->revoke($link);

        $this->auditService->log(auth()->user(), 'link.revoked', 'document_access_link', $link->id, [
            'document_id' => $document->id,
        ]);

        return back()->with('success', 'Link revoked.');
    }

    public function access(string $token)
    {
        $link = DocumentAccessLink::where('token', $token)->firstOrFail();

        if ($link->isExpired()) {
            abort(404);
        }

        if ($link->role === 'editor' && !auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $document = $link->document()->with('owner', 'division', 'currentVersion', 'versions.author')->firstOrFail();

        return view('documents.shared', compact('document', 'link'));
    }

    public function save(Request $request, string $token): RedirectResponse
    {
        $link = DocumentAccessLink::where('token', $token)->firstOrFail();

        if ($link->isExpired() || $link->role !== 'editor') {
            abort(403);
        }

        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $document = $link->document;

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $versionService = app(\App\Services\VersionService::class);
        $version = $versionService->savePending($document, $validated['content'], auth()->user());

        $auditService = app(\App\Services\AuditService::class);
        $auditService->log(auth()->user(), 'version.created_via_link', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
            'link_token' => $token,
        ]);

        return redirect()->route('shared.documents', $token)->with('success', 'Edit saved. Pending approval.');
    }

    public function upload(Request $request, string $token)
    {
        $link = DocumentAccessLink::where('token', $token)->firstOrFail();

        if ($link->isExpired() || $link->role !== 'editor') {
            abort(403);
        }

        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        return app(JoditController::class)->upload($request);
    }

    public function discard(string $token): RedirectResponse
    {
        $link = DocumentAccessLink::where('token', $token)->firstOrFail();

        if ($link->isExpired() || $link->role !== 'editor') {
            abort(403);
        }

        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $document = $link->document;

        $versionService = app(\App\Services\VersionService::class);
        $discarded = $versionService->discardPending($document);

        $auditService = app(\App\Services\AuditService::class);
        if ($discarded) {
            $auditService->log(null, 'version.discarded_via_link', 'document_version', $discarded->id, [
                'document_id' => $document->id,
                'version_number' => $discarded->version_number,
                'link_token' => $token,
            ]);
        }

        return redirect()->route('shared.documents', $token)->with('success', $discarded
            ? 'Versi pending v' . $discarded->version_number . ' di-discard.'
            : 'Tidak ada versi pending untuk di-discard.');
    }

    public function history(): View
    {
        $logs = AuditLog::where('user_id', auth()->id())
            ->where('action', 'version.created_via_link')
            ->latest('created_at')
            ->get()
            ->unique(fn ($log) => $log->metadata['document_id'] ?? null)
            ->values();

        $documents = Document::whereIn('id', $logs->pluck('metadata.document_id'))
            ->with('division', 'currentVersion')
            ->get()
            ->keyBy('id');

        $links = DocumentAccessLink::whereIn('token', $logs->pluck('metadata.link_token'))
            ->get()
            ->keyBy('token');

        $history = $logs->map(function ($log) use ($documents, $links) {
            $docId = $log->metadata['document_id'] ?? null;
            $token = $log->metadata['link_token'] ?? null;

            return [
                'document' => $documents->get($docId),
                'link' => $links->get($token),
                'token' => $token,
                'version_number' => $log->metadata['version_number'] ?? null,
                'edited_at' => $log->created_at,
            ];
        })->filter(fn ($h) => $h['document'] && $h['token']);

        return view('documents.shared-history', compact('history'));
    }
}
