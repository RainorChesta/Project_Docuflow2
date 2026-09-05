<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentDivisionShare;
use App\Models\DocumentShare;
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
            'type' => 'required|in:user,division',
            'user_id' => 'required_without:division_id|exists:users,id',
            'division_id' => 'required_without:user_id|exists:divisions,id',
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
            $division = Division::findOrFail($validated['division_id']);
            $share = $this->shareService->addDivisionShare(
                $document,
                $division,
                $validated['role'],
                $invitedBy,
            );
            $this->auditService->log($invitedBy, 'share.division.added', 'document_division_share', $share->id, [
                'document_id' => $document->id,
                'division_id' => $validated['division_id'],
                'role' => $validated['role'],
            ]);
        }

        return back()->with('notice', 'Akses berhasil ditambahkan.');
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

        return back()->with('notice', 'Peran pengguna diperbarui.');
    }

    public function destroyUserShare(Document $document, DocumentShare $share): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $this->shareService->removeUserShare($share, auth()->user());
        $this->auditService->log(auth()->user(), 'share.user.removed', 'document_share', $share->id, [
            'document_id' => $document->id,
        ]);

        return back()->with('notice', 'Akses pengguna dihapus.');
    }

    public function updateDivisionShare(Request $request, Document $document, DocumentDivisionShare $divisionShare): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $validated = $request->validate(['role' => 'required|in:editor,viewer']);

        $this->shareService->updateDivisionShareRole($divisionShare, $validated['role']);
        $this->auditService->log(auth()->user(), 'share.division.updated', 'document_division_share', $divisionShare->id, [
            'document_id' => $document->id,
            'role' => $validated['role'],
        ]);

        $division = $divisionShare->division;
        if ($division) {
            $divisionUsers = User::where('division_id', $division->id)
                ->where('is_active', true)
                ->where('id', '!=', auth()->id())
                ->get();

            foreach ($divisionUsers as $member) {
                $member->notify(new \App\Notifications\DocumentSharedWithDivision(
                    $document,
                    $division->name,
                    $validated['role'],
                    auth()->user()->name
                ));
            }
        }

        return back()->with('notice', 'Peran divisi diperbarui.');
    }

    public function destroyDivisionShare(Document $document, DocumentDivisionShare $divisionShare): RedirectResponse
    {
        $this->authorize('manageAccess', $document);

        $this->shareService->removeDivisionShare($divisionShare, auth()->user());
        $this->auditService->log(auth()->user(), 'share.division.removed', 'document_division_share', $divisionShare->id, [
            'document_id' => $document->id,
        ]);

        return back()->with('notice', 'Akses divisi dihapus.');
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

        return back()->with('notice', 'Pengaturan akses umum diperbarui.');
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

        $document->load(['shares.user', 'divisionShares.division']);

        return response()->json([
            'owner' => [
                'id' => $document->owner_id,
                'name' => $document->owner?->name,
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
                'role' => $s->role,
            ]),
            'division_shares' => $document->divisionShares->map(fn(DocumentDivisionShare $s) => [
                'id' => $s->id,
                'division_id' => $s->division_id,
                'name' => $s->division?->name,
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
            ->get(['id', 'name', 'email']);

        $divisions = Division::query()
            ->when($term !== '', fn($q) => $q->where('name', 'like', "%{$term}%"))
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json([
            'users' => $users,
            'divisions' => $divisions,
        ]);
    }

    /**
     * Open a document via its share_token link.
     */
    public function accessByToken(string $token, \App\Services\OnlyOfficeService $onlyOfficeService)
    {
        $document = Document::where('share_token', $token)->firstOrFail();

        $this->authorize('view', $document);

        $currentUser = auth()->user();

        if ($currentUser) {
            // Mark unread share notifications for this document as read
            $currentUser->unreadNotifications()
                ->where('data->type', 'document_shared')
                ->where('data->document_id', $document->id)
                ->update(['read_at' => now()]);
        }

        // Notify document owner when another user opens the document via the share link (throttled)
        if ($document->owner_id && $currentUser && $currentUser->id !== $document->owner_id) {
            $throttleKey = 'notif_doc_opened_link_' . $document->id . '_' . $currentUser->id;
            if (\Illuminate\Support\Facades\Cache::add($throttleKey, true, now()->addMinutes(15))) {
                $document->owner?->notify(new \App\Notifications\DocumentOpenedViaLink($document, $currentUser->name));
            }
        }

        $document->load('owner', 'division', 'documentType', 'currentVersion', 'versions.author', 'shares.user', 'divisionShares.division');

        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();

        $version = $document->displayVersion();
        $onlyOfficeConfig = null;
        if ($version) {
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
            ->with('targetUser.signature')
            ->get()
            ->map(function ($req) use ($onlyOfficeService) {
                if (!$req->targetUser || !$req->targetUser->hasSignature()) {
                    return null;
                }
                return [
                    'request_id' => $req->id,
                    'url' => $onlyOfficeService->getSignatureFileUrl($req->targetUser),
                    'target_user_name' => $req->targetUser->name,
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        return view('documents.show', compact('document', 'divisions', 'onlyOfficeConfig', 'version', 'approvedSignatures', 'companies'));
    }
}