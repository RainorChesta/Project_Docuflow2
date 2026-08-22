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
        if ($request->filled('search') || $request->filled('document_type_id')) {
            $results = Document::with('owner', 'division', 'currentVersion')
                ->visibleTo($user)
                ->when($request->filled('search'), function ($q) use ($request) {
                    $q->where(function ($q) use ($request) {
                        $q->where('title', 'like', "%{$request->get('search')}%")
                          ->orWhere('document_number', 'like', "%{$request->get('search')}%");
                    });
                })
                ->when($request->filled('document_type_id'), fn($q) => $q->where('document_type_id', $request->get('document_type_id')))
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        // Admin dashboard = General Dokumen list (tab removed from navbar for admin).
        if ($user->isAdmin()) {
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

            $divisions = Division::all();
            $documentTypes = DocumentType::orderBy('name')->get();

            return view('dashboard', compact('results', 'documents', 'divisions', 'documentTypes'));
        }

        $recent = $user->documents()->with('division', 'currentVersion')
            ->latest()
            ->take(5)
            ->get();

        $expiringDocuments = $user->documents()->with('division')
            ->where('is_expired', false)
            ->get()
            ->filter(function ($doc) {
                if (!$doc->expires_at) return false;
                $days = now()->startOfDay()->diffInDays($doc->expires_at->startOfDay(), false);
                return $days >= 0 && $days <= 30;
            })
            ->sortBy(fn($doc) => $doc->expires_at);

        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dashboard', compact('results', 'recent', 'documentTypes', 'expiringDocuments'));
    }
}
