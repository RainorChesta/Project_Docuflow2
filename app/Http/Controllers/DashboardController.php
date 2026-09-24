<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Setting;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);
        $userBranchIds = $user->allBranchIds();
        $userCompanyIds = $user->allCompanyIds();

        // Search across every document the user may see.
        $results = null;
        if ($request->filled('search') || $request->filled('document_type_id')) {
            $searchQuery = Document::with('owner', 'unitKerja', 'currentVersion')
                ->visibleTo($user);

            if (!$user->isAdmin()) {
                if ($activeBranchId) {
                    $searchQuery->where(function ($q) use ($activeBranchId, $activeCompanyId) {
                        $q->where('branch_id', $activeBranchId)
                          ->orWhere(function ($sub) use ($activeCompanyId) {
                              $sub->whereNull('branch_id')
                                  ->where('company_id', $activeCompanyId);
                          })
                          ->orWhereHas('distributions', fn($dq) => $dq->where('target_branch_id', $activeBranchId));
                    });
                } elseif ($activeCompanyId) {
                    $searchQuery->where(function ($q) use ($activeCompanyId) {
                        $q->where('company_id', $activeCompanyId)
                          ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId))
                          ->orWhereHas('distributions', fn($dq) => $dq->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId)));
                    });
                }
            }

            $results = $searchQuery
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
            $query = Document::with('owner', 'unitKerja', 'currentVersion', 'versions')
                ->general();

            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            if ($unitKerjaId = ($request->get('unit_kerja_id') ?? $request->get('division_id'))) {
                $query->where('unit_kerja_id', $unitKerjaId);
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

            $unitKerjas = UnitKerja::orderBy('code')->get();
            $documentTypes = DocumentType::orderBy('name')->get();

            return view('dashboard', compact('results', 'documents', 'unitKerjas', 'documentTypes'));
        }

        $baseDocQuery = $user->documents();
        if ($activeBranchId) {
            $baseDocQuery->where('branch_id', $activeBranchId);
        } elseif ($activeCompanyId) {
            $baseDocQuery->where(function ($q) use ($activeCompanyId) {
                $q->where('company_id', $activeCompanyId)
                  ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
            });
        } elseif (!empty($userBranchIds)) {
            $baseDocQuery->where(function ($q) use ($userBranchIds, $userCompanyIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->orWhere(function ($sub) use ($userCompanyIds) {
                      $sub->whereNull('branch_id')
                          ->whereIn('company_id', $userCompanyIds);
                  });
            });
        } elseif (!empty($userCompanyIds)) {
            $baseDocQuery->whereIn('company_id', $userCompanyIds);
        }

        $totalDocsCount = (clone $baseDocQuery)->count();
        $activeDocsCount = (clone $baseDocQuery)->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))->count();
        $pendingDocsCount = (clone $baseDocQuery)->whereHas('versions', fn($q) => $q->where('status', 'pending'))->count();

        $recent = (clone $baseDocQuery)->with('unitKerja', 'currentVersion')->latest()->take(5)->get();

        $retentionYears = (int) Setting::get('document_retention_years', config('app.document_retention_years', 2));
        $now = now();
        $in30Days = $now->copy()->addDays(30);

        $expiringDocuments = (clone $baseDocQuery)
            ->with('unitKerja')
            ->where('is_expired', false)
            ->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
            ->where(function ($q) use ($now, $in30Days, $retentionYears) {
                $q->whereBetween('expiration_date', [$now->toDateString(), $in30Days->toDateString()])
                  ->orWhere(function ($fallback) use ($now, $in30Days, $retentionYears) {
                      $fallback->whereNull('expiration_date')
                               ->whereBetween('created_at', [
                                   $now->copy()->subYears($retentionYears)->toDateTimeString(),
                                   $in30Days->copy()->subYears($retentionYears)->toDateTimeString()
                               ]);
                  });
            })
            ->get()
            ->sortBy(fn($doc) => $doc->expires_at);

        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dashboard', compact(
            'results',
            'recent',
            'documentTypes',
            'expiringDocuments',
            'totalDocsCount',
            'activeDocsCount',
            'pendingDocsCount'
        ));
    }
}
