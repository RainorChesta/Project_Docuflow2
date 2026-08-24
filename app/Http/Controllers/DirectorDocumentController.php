<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectorDocumentController extends Controller
{
    /**
     * Display the Accordion view of all Companies, Branches, and Documents for Director.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $search = $request->get('search');
        $selectedCompanyId = $request->get('company_id');
        $selectedBranchId = $request->get('branch_id');

        $companiesQuery = Company::query();
        if (!$user->isAdmin()) {
            $companiesQuery->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
        }

        $companies = $companiesQuery->with(['branches' => function ($query) use ($search, $user) {
            if (!$user->isAdmin()) {
                $query->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
            }
            $query->withCount(['documents' => function ($docQuery) use ($search) {
                if ($search) {
                    $docQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                }
            }]);
        }])->orderBy('name')->get();

        // If specific branch is selected, fetch its documents
        $documents = collect();
        if ($selectedBranchId) {
            if (!$user->isAdmin() && !$user->branches()->where('branches.id', $selectedBranchId)->exists()) {
                abort(403, 'Unauthorized branch access.');
            }

            $docQuery = Document::where('branch_id', $selectedBranchId)
                ->with(['owner', 'division', 'documentType', 'currentVersion', 'versions']);

            if ($search) {
                $docQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            $documents = $docQuery->latest()->paginate(15);
        }

        return view('director.documents.index', compact('companies', 'documents', 'selectedCompanyId', 'selectedBranchId', 'search'));
    }
}
