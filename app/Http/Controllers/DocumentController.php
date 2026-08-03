<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Division;
use App\Models\DocumentType;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\VersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected VersionService $versionService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        // Tab selection: general | mine | division
        $type = $request->get('type', 'general');

        $query = Document::with('owner', 'division', 'currentVersion', 'versions');

        if ($type === 'general') {
            $query->general();
        } elseif ($type === 'mine') {
            $query->ownedBy($user);
        } else {
            $query->division($user);
        }
        $query = Document::with('owner', 'division', 'currentVersion')
            // Only show documents already approved by the head; pending ones are hidden.
            ->whereHas('currentVersion')
            ->where(function ($q) use ($user) {
                $q->where('division_id', $user->division_id)
                  ->orWhere('is_public', true);
            });

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($divisionId = $request->get('division_id')) {
            $query->where('division_id', $divisionId);
        }

        if ($documentTypeId = $request->get('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->whereHas('currentVersion', fn($q) => $q->where('status', 'active'));
            } elseif ($status === 'pending') {
                $query->whereDoesntHave('currentVersion')
                    ->orWhereHas('versions', fn($q) => $q->where('status', 'pending'));
            } elseif ($status === 'draft') {
                $query->whereDoesntHave('versions');
            }
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        $divisions = $user->isAdmin()
            ? Division::all()
            : Division::whereIn('id', $user->allDivisionIds())->get();

        return view('documents.index', compact('documents', 'divisions', 'type'));
    }

    public function create(): View
    {
        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();

        return view('documents.create', compact('documentTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
        ]);

        // Documents created here are always division-scoped; scope is
        // changed later from the document's own settings.
        $validated['visibility'] = Document::VISIBILITY_DIVISION;

        // Users may only assign documents to divisions they belong to.
        if (!auth()->user()->isAdmin()
            && !in_array((int) $validated['division_id'], auth()->user()->allDivisionIds(), true)) {
            abort(403, 'You cannot create documents in this division.');
        }

        $doc = $this->documentService->create($validated, auth()->id());

        $this->auditService->log(auth()->user(), 'document.created', 'document', $doc->id, [
            'title' => $doc->title,
            'document_number' => $doc->document_number,
            'visibility' => $doc->visibility,
        ]);

        return redirect()->route('documents.edit', $doc)->with('success', 'Document created. Fill in the content.');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'division', 'documentType', 'currentVersion', 'versions.author');

        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();

        return view('documents.show', compact('document', 'divisions'));
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $document->load('currentVersion', 'versions');

        return view('documents.insert', compact('document'));
    }

    public function preview(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'division', 'documentType', 'currentVersion');

        return view('documents.preview', compact('document'));
    }

    public function save(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        // savePending updates the existing pending version in place — no new version.
        $version = $this->versionService->savePending($document, $validated['content'], auth()->user());

        $this->auditService->log(auth()->user(), 'version.created', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        $message = $version->wasRecentlyCreated
            ? 'Edit saved. Pending approval.'
            : 'Versi v' . $version->version_number . ' diperbarui (tetap menunggu approval).';

        return redirect()->route('documents.show', $document)->with('success', $message);
    }

    public function discard(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $discarded = $this->versionService->discardPending($document);

        if ($discarded) {
            $this->auditService->log(auth()->user(), 'version.discarded', 'document_version', $discarded->id, [
                'document_id' => $document->id,
                'version_number' => $discarded->version_number,
            ]);
        }

        return redirect()->route('documents.show', $document)->with('success', $discarded
            ? 'Versi pending v' . $discarded->version_number . ' di-discard.'
            : 'Tidak ada versi pending untuk di-discard.');
    }

    public function saveDraft(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $this->versionService->saveDraft($document, $validated['content'], auth()->user());

        return redirect()->route('documents.show', $document)->with('success', 'Draft saved.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document discarded.');
    }

    /**
     * Change a document's visibility scope (general / division / personal).
     */
    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'visibility' => 'required|in:general,division,personal',
            'division_id' => 'nullable|required_if:visibility,division|exists:divisions,id',
        ]);

        if ($validated['visibility'] === 'division'
            && !auth()->user()->isAdmin()
            && !in_array((int) $validated['division_id'], auth()->user()->allDivisionIds(), true)) {
            abort(403, 'You cannot assign this document to this division.');
        }

        $document->update([
            'visibility' => $validated['visibility'],
            // Division-scoped docs require a division; other scopes drop it.
            'division_id' => $validated['visibility'] === 'division'
                ? $validated['division_id']
                : null,
            // Legacy derived flag stays in sync with the scope.
            'is_public' => $validated['visibility'] === Document::VISIBILITY_GENERAL,
        ]);

        $this->auditService->log(auth()->user(), 'document.visibility_changed', 'document', $document->id, [
            'visibility' => $validated['visibility'],
            'division_id' => $document->division_id,
        ]);

        return back()->with('success', 'Document visibility updated.');
    }
}