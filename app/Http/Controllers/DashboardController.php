<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Search across every document the user may see.
        $results = null;
        if ($search = $request->get('search')) {
            $results = Document::with('owner', 'division', 'currentVersion')
                ->visibleTo($user)
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        // General documents — same query as the "General Dokumen" tab.
        $query = Document::with('owner', 'division', 'currentVersion', 'versions')
            ->general();

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

        $documentTypes = DocumentType::orderBy('name')->get();
        $type = 'general';

        return view('dashboard', compact('results', 'documents', 'divisions', 'documentTypes', 'type'));
    }
}
