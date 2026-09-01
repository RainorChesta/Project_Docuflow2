<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TrashController extends Controller
{
    /**
     * Get a query for soft-deleted documents scoped to the user's role and organizational hierarchy.
     */
    protected function getScopedTrashQuery(User $user): Builder
    {
        $query = Document::onlyTrashed()
            ->with(['owner', 'division', 'documentType', 'branch.company', 'versions']);

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isDirector()) {
            $branchIds = $user->allBranchIds();
            $companyIds = $user->allCompanyIds();

            return $query->where(function ($q) use ($branchIds, $companyIds, $user) {
                $q->where('owner_id', $user->id);

                if (!empty($branchIds)) {
                    $q->orWhereIn('branch_id', $branchIds)
                      ->orWhere(function ($sub) use ($companyIds) {
                          $sub->whereNull('branch_id')->whereIn('company_id', $companyIds);
                      });
                } elseif (!empty($companyIds)) {
                    $q->orWhereIn('company_id', $companyIds);
                }
            });
        }

        if ($user->isHead()) {
            $divisionIds = $user->allDivisionIds();

            return $query->where(function ($q) use ($user, $divisionIds) {
                $q->where('owner_id', $user->id)
                  ->orWhere(function ($sub) use ($divisionIds) {
                      $sub->whereIn('division_id', $divisionIds)
                          ->where('visibility', Document::VISIBILITY_DIVISION);
                  });
            });
        }

        // Regular Staff: only see documents they own
        return $query->where('owner_id', $user->id);
    }

    /**
     * Display a listing of soft-deleted documents.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = $request->get('search');

        $query = $this->getScopedTrashQuery($user);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $trashedDocuments = $query->latest('deleted_at')->paginate(15)->withQueryString();

        return view('trash.index', compact('trashedDocuments', 'search'));
    }

    /**
     * Restore a soft-deleted document.
     */
    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $document = $this->getScopedTrashQuery($user)->findOrFail($id);

        $this->authorize('restore', $document);

        $document->restore();

        return redirect()->route('trash.index')
            ->with('success', __('Dokumen \":title\" berhasil dipulihkan dari sampah.', ['title' => $document->title]));
    }

    /**
     * Restore selected soft-deleted documents.
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $trashedDocuments = $this->getScopedTrashQuery($user)
            ->whereIn('id', $validated['ids'])
            ->get();

        $count = 0;
        foreach ($trashedDocuments as $document) {
            if ($user->can('restore', $document)) {
                $document->restore();
                $count++;
            }
        }

        return redirect()->route('trash.index')
            ->with('success', __(':count dokumen terpilih berhasil dipulihkan dari sampah.', ['count' => $count]));
    }

    /**
     * Permanently delete a soft-deleted document.
     */
    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $document = $this->getScopedTrashQuery($user)->findOrFail($id);

        $this->authorize('forceDelete', $document);

        $this->permanentlyDeleteDocument($document);

        return redirect()->route('trash.index')
            ->with('success', __('Dokumen \":title\" berhasil dihapus secara permanen.', ['title' => $document->title]));
    }

    /**
     * Permanently delete selected soft-deleted documents.
     */
    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $trashedDocuments = $this->getScopedTrashQuery($user)
            ->whereIn('id', $validated['ids'])
            ->get();

        $count = 0;
        foreach ($trashedDocuments as $document) {
            if ($user->can('forceDelete', $document)) {
                $this->permanentlyDeleteDocument($document);
                $count++;
            }
        }

        return redirect()->route('trash.index')
            ->with('success', __(':count dokumen terpilih berhasil dihapus secara permanen.', ['count' => $count]));
    }

    /**
     * Permanently delete all soft-deleted documents in user scope.
     */
    public function emptyTrash(Request $request): RedirectResponse
    {
        $user = $request->user();
        $trashedDocuments = $this->getScopedTrashQuery($user)->get();
        $count = 0;

        foreach ($trashedDocuments as $document) {
            if ($user->can('forceDelete', $document)) {
                $this->permanentlyDeleteDocument($document);
                $count++;
            }
        }

        return redirect()->route('trash.index')
            ->with('success', __(':count dokumen di tempat sampah berhasil dihapus secara permanen.', ['count' => $count]));
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
