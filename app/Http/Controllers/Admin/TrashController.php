<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TrashController extends Controller
{
    /**
     * Display a listing of soft-deleted documents.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $search = $request->get('search');

        $query = Document::onlyTrashed()
            ->with(['owner', 'division', 'documentType', 'branch.company']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $trashedDocuments = $query->latest('deleted_at')->paginate(15)->withQueryString();

        return view('admin.trash.index', compact('trashedDocuments', 'search'));
    }

    /**
     * Restore a soft-deleted document.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->authorize('admin');

        $document = Document::onlyTrashed()->findOrFail($id);
        $document->restore();

        return redirect()->route('admin.trash.index')
            ->with('success', __('Dokumen ":title" berhasil dipulihkan dari sampah.', ['title' => $document->title]));
    }

    /**
     * Permanently delete a single soft-deleted document.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $this->authorize('admin');

        $document = Document::onlyTrashed()->findOrFail($id);
        $title = $document->title;
        $this->permanentlyDeleteDocument($document);

        return redirect()->route('admin.trash.index')
            ->with('success', __('Dokumen ":title" berhasil dihapus secara permanen.', ['title' => $title]));
    }

    /**
     * Permanently delete all soft-deleted documents (Empty Trash).
     */
    public function emptyTrash(): RedirectResponse
    {
        $this->authorize('admin');

        $trashedDocuments = Document::onlyTrashed()->get();
        $count = $trashedDocuments->count();

        foreach ($trashedDocuments as $document) {
            $this->permanentlyDeleteDocument($document);
        }

        return redirect()->route('admin.trash.index')
            ->with('success', __(':count dokumen di tempat sampah berhasil dihapus secara permanen.', ['count' => $count]));
    }

    /**
     * Permanently delete selected soft-deleted documents.
     */
    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $trashedDocuments = Document::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $count = $trashedDocuments->count();

        foreach ($trashedDocuments as $document) {
            $this->permanentlyDeleteDocument($document);
        }

        return redirect()->route('admin.trash.index')
            ->with('success', __(':count dokumen terpilih berhasil dihapus secara permanen.', ['count' => $count]));
    }

    /**
     * Restore selected soft-deleted documents.
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $trashedDocuments = Document::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $count = $trashedDocuments->count();

        foreach ($trashedDocuments as $document) {
            $document->restore();
        }

        return redirect()->route('admin.trash.index')
            ->with('success', __(':count dokumen terpilih berhasil dipulihkan dari sampah.', ['count' => $count]));
    }

    /**
     * Helper to clean up disk storage and force delete document.
     */
    private function permanentlyDeleteDocument(Document $document): void
    {
        foreach ($document->versions as $version) {
            if ($version->file_path) {
                Storage::disk('local')->delete($version->file_path);
            }
        }
        $document->versions()->delete();
        $document->forceDelete();
    }
}
