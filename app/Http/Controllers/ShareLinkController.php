<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Services\AccessLinkService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $link = $this->accessLinkService->create(
            $document,
            $validated['role'],
            $validated['expires_at'] ?? null,
            auth()->user()
        );

        $this->auditService->log(auth()->user(), 'link.created', 'document_access_link', $link->id, [
            'document_id' => $document->id,
            'role' => $link->role,
        ]);

        return back()->with('success', route('shared.documents', $link->token));
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

        $document = $link->document()->with('owner', 'division', 'currentVersion', 'versions.author')->firstOrFail();

        return view('documents.shared', compact('document', 'link'));
    }

    public function save(Request $request, string $token): RedirectResponse
    {
        $link = DocumentAccessLink::where('token', $token)->firstOrFail();

        if ($link->isExpired() || $link->role !== 'editor') {
            abort(403);
        }

        $document = $link->document;

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $versionService = app(\App\Services\VersionService::class);
        $version = $versionService->savePending($document, $validated['content'], auth()->user() ?? $document->owner);

        // Use a system audit since no authenticated user
        $auditService = app(\App\Services\AuditService::class);
        $auditService->log(null, 'version.created_via_link', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
            'link_token' => $token,
        ]);

        return redirect()->route('shared.documents', $token)->with('success', 'Edit saved. Pending approval.');
    }
}
