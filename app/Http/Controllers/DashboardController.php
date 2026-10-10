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
            $searchQuery = Document::with(['owner', 'unitKerja', 'currentVersion', 'versions'])
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

        // Admin & Direktur dashboard: Comprehensive executive analytics dashboard with interactive charts & insights
        if ($user->isAdmin() || $user->isDirector()) {
            $isDirector = $user->isDirector();

            // Build scoped document query for Director or global for Admin
            $scopedDocQuery = Document::query();
            if ($isDirector) {
                $scopedDocQuery = Document::visibleTo($user);
                if ($activeBranchId) {
                    $scopedDocQuery->where(function ($q) use ($activeBranchId, $activeCompanyId) {
                        $q->where('branch_id', $activeBranchId)
                          ->orWhere(function ($sub) use ($activeCompanyId) {
                              $sub->whereNull('branch_id')->where('company_id', $activeCompanyId);
                          })
                          ->orWhereHas('distributions', fn($dq) => $dq->where('target_branch_id', $activeBranchId));
                    });
                } elseif ($activeCompanyId) {
                    $scopedDocQuery->where(function ($q) use ($activeCompanyId) {
                        $q->where('company_id', $activeCompanyId)
                          ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId))
                          ->orWhereHas('distributions', fn($dq) => $dq->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId)));
                    });
                } elseif (!empty($userBranchIds)) {
                    $scopedDocQuery->where(function ($q) use ($userBranchIds, $userCompanyIds) {
                        $q->whereIn('branch_id', $userBranchIds)
                          ->orWhere(function ($sub) use ($userCompanyIds) {
                              $sub->whereNull('branch_id')->whereIn('company_id', $userCompanyIds);
                          });
                    });
                } elseif (!empty($userCompanyIds)) {
                    $scopedDocQuery->whereIn('company_id', $userCompanyIds);
                }
            }

            // Stats Counts
            $totalDocsCount = (clone $scopedDocQuery)->count();
            $activeDocsCount = (clone $scopedDocQuery)
                ->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
                ->count();
            $pendingDocsCount = (clone $scopedDocQuery)->whereHas('versions', fn($q) => $q->where('status', 'pending'))->count();
            $draftDocsCount = (clone $scopedDocQuery)->where(function ($q) {
                $q->whereHas('versions', fn($v) => $v->where('status', 'draft'))
                  ->orWhereDoesntHave('versions');
            })->count();
            $expiredDocsCount = 0;
            
            $totalUsersCount = \App\Models\User::count();
            $totalUnitKerjaCount = UnitKerja::count();

            // Unread Director Tembusan Count
            $unreadTembusanCount = (clone $scopedDocQuery)
                ->whereNotNull('director_notified_at')
                ->whereNull('director_read_at')
                ->count();

            // Compliance & Approval Rate (% Dokumen aktif terhadap total)
            $complianceRate = $totalDocsCount > 0 ? round(($activeDocsCount / $totalDocsCount) * 100, 1) : 0;

            // Trend Data: 8 hari terakhir (Sesuai grafik tren foto referensi)
            $days = 8;
            $chartDates = [];
            $chartCreated = [];
            $chartActive = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateStr = $date->format('Y-m-d');
                $label = $date->translatedFormat('d M');
                $chartDates[] = $label;

                $createdCount = (clone $scopedDocQuery)->whereDate('created_at', $dateStr)->count();
                $chartCreated[] = $createdCount;

                $activeCount = (clone $scopedDocQuery)->whereHas('currentVersion', function ($vq) use ($dateStr) {
                    $vq->where('status', 'active')
                       ->where(function ($sub) use ($dateStr) {
                           $sub->whereDate('reviewed_at', $dateStr)
                               ->orWhere(function ($s2) use ($dateStr) {
                                   $s2->whereNull('reviewed_at')
                                      ->whereDate('created_at', $dateStr);
                               });
                       });
                })->count();
                $chartActive[] = $activeCount;
            }

            // Top Categories (Document Types) Distribution for Donut Chart
            $docTypeCounts = (clone $scopedDocQuery)
                ->whereNotNull('document_type_id')
                ->selectRaw('document_type_id, count(*) as total')
                ->groupBy('document_type_id')
                ->orderByDesc('total')
                ->take(4)
                ->pluck('total', 'document_type_id');

            $docTypesMap = DocumentType::whereIn('id', $docTypeCounts->keys())->get()->keyBy('id');
            $docTypeLabels = [];
            $docTypeSeries = [];
            $topDocTypeSum = 0;

            foreach ($docTypeCounts as $dtId => $count) {
                if (isset($docTypesMap[$dtId])) {
                    $docTypeLabels[] = $docTypesMap[$dtId]->name;
                    $docTypeSeries[] = (int) $count;
                    $topDocTypeSum += $count;
                }
            }

            $otherDocTypesCount = max(0, $totalDocsCount - $topDocTypeSum);
            if ($otherDocTypesCount > 0 && count($docTypeLabels) > 0) {
                $docTypeLabels[] = __('Lainnya');
                $docTypeSeries[] = (int) $otherDocTypesCount;
            }

            // Fallback if no categorized documents yet
            if (empty($docTypeSeries)) {
                $defaultTypes = DocumentType::take(4)->get();
                foreach ($defaultTypes as $dt) {
                    $docTypeLabels[] = $dt->name;
                    $docTypeSeries[] = 0;
                }
            }

            // Top Active Unit Kerja with Progress Metrics
            $unitKerjaCounts = (clone $scopedDocQuery)
                ->whereNotNull('unit_kerja_id')
                ->selectRaw('unit_kerja_id, count(*) as total')
                ->groupBy('unit_kerja_id')
                ->orderByDesc('total')
                ->take(4)
                ->pluck('total', 'unit_kerja_id');

            $unitKerjasMap = UnitKerja::whereIn('id', $unitKerjaCounts->keys())->get()->keyBy('id');
            $topUnitKerjas = [];
            foreach ($unitKerjaCounts as $ukId => $count) {
                if (isset($unitKerjasMap[$ukId])) {
                    $uk = $unitKerjasMap[$ukId];
                    $percentage = $totalDocsCount > 0 ? round(($count / $totalDocsCount) * 100, 1) : 0;
                    $topUnitKerjas[] = [
                        'name' => $uk->nama_unit_kerja,
                        'code' => $uk->kode_unit_kerja,
                        'count' => $count,
                        'percentage' => $percentage,
                    ];
                }
            }

            // Recent Documents
            $recentDocuments = (clone $scopedDocQuery)
                ->with(['owner', 'unitKerja', 'currentVersion', 'versions', 'documentType', 'branch.company', 'company'])
                ->latest()
                ->take(6)
                ->get();

            // Expiration recaps for Director / Admin / PIC
            $ownerDocQuery = Document::withoutTrashed()
                ->where('owner_id', $user->id)
                ->whereNotNull('expiration_date')
                ->where(function ($q) {
                    $q->whereNotNull('current_version_id')
                      ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                      ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
                })
                ->with(['unitKerja', 'currentVersion'])
                ->get();
            $ownerExpiringDocs = $ownerDocQuery->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())->sortBy(fn($d) => $d->expiration_date);

            $isPicUnitKerja = $user->isPicUnitKerja();
            $picExpiringDocs = collect();
            if ($isPicUnitKerja) {
                $managedUnitIds = $user->managedUnitKerjaIds();
                $picExpiringDocs = Document::withoutTrashed()
                    ->whereIn('unit_kerja_id', $managedUnitIds)
                    ->whereNotNull('expiration_date')
                    ->where(function ($q) {
                        $q->whereNotNull('current_version_id')
                          ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                          ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
                    })
                    ->with(['owner', 'unitKerja', 'currentVersion'])
                    ->get()
                    ->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())
                    ->sortBy(fn($d) => $d->expiration_date);
            }

            $unitKerjaIds = $user->allUnitKerjaIds();
            $sharedExpiringDocs = Document::withoutTrashed()
                ->where('owner_id', '!=', $user->id)
                ->whereNotNull('expiration_date')
                ->where(function ($q) use ($user, $unitKerjaIds) {
                    $q->whereHas('shares', fn($sq) => $sq->where('user_id', $user->id));
                    if (!empty($unitKerjaIds)) {
                        $q->orWhereHas('unitKerjaShares', fn($dq) => $dq->whereIn('unit_kerja_id', $unitKerjaIds));
                    }
                })
                ->where(function ($q) {
                    $q->whereNotNull('current_version_id')
                      ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                      ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
                })
                ->with(['owner', 'unitKerja', 'currentVersion'])
                ->get()
                ->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())
                ->sortBy(fn($d) => $d->expiration_date);

            $expiringDocuments = $picExpiringDocs->isNotEmpty() ? $picExpiringDocs : $ownerExpiringDocs;
            $unitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();
            $documentTypes = DocumentType::orderBy('name')->get();

            return view('dashboard', compact(
                'results',
                'totalDocsCount',
                'activeDocsCount',
                'pendingDocsCount',
                'draftDocsCount',
                'expiredDocsCount',
                'unreadTembusanCount',
                'totalUsersCount',
                'totalUnitKerjaCount',
                'complianceRate',
                'chartDates',
                'chartCreated',
                'chartActive',
                'docTypeLabels',
                'docTypeSeries',
                'topUnitKerjas',
                'recentDocuments',
                'expiringDocuments',
                'ownerExpiringDocs',
                'sharedExpiringDocs',
                'picExpiringDocs',
                'isPicUnitKerja',
                'unitKerjas',
                'documentTypes'
            ));
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

        $recent = (clone $baseDocQuery)->with(['owner', 'unitKerja', 'currentVersion', 'versions'])->latest()->take(5)->get();

        // Expiration recaps for Staff / General User
        $ownerDocQuery = Document::withoutTrashed()
            ->where('owner_id', $user->id)
            ->whereNotNull('expiration_date')
            ->where(function ($q) {
                $q->whereNotNull('current_version_id')
                  ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                  ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
            })
            ->with(['unitKerja', 'currentVersion'])
            ->get();
        $ownerExpiringDocs = $ownerDocQuery->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())->sortBy(fn($d) => $d->expiration_date);

        $isPicUnitKerja = $user->isPicUnitKerja();
        $picExpiringDocs = collect();
        if ($isPicUnitKerja) {
            $managedUnitIds = $user->managedUnitKerjaIds();
            $picExpiringDocs = Document::withoutTrashed()
                ->whereIn('unit_kerja_id', $managedUnitIds)
                ->whereNotNull('expiration_date')
                ->where(function ($q) {
                    $q->whereNotNull('current_version_id')
                      ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                      ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
                })
                ->with(['owner', 'unitKerja', 'currentVersion'])
                ->get()
                ->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())
                ->sortBy(fn($d) => $d->expiration_date);
        }

        $unitKerjaIds = $user->allUnitKerjaIds();
        $sharedExpiringDocs = Document::withoutTrashed()
            ->where('owner_id', '!=', $user->id)
            ->whereNotNull('expiration_date')
            ->where(function ($q) use ($user, $unitKerjaIds) {
                $q->whereHas('shares', fn($sq) => $sq->where('user_id', $user->id));
                if (!empty($unitKerjaIds)) {
                    $q->orWhereHas('unitKerjaShares', fn($dq) => $dq->whereIn('unit_kerja_id', $unitKerjaIds));
                }
            })
            ->where(function ($q) {
                $q->whereNotNull('current_version_id')
                  ->whereHas('currentVersion', fn($sub) => $sub->where('status', 'active'))
                  ->orWhereHas('versions', fn($sub) => $sub->where('status', 'active'));
            })
            ->with(['owner', 'unitKerja', 'currentVersion'])
            ->get()
            ->filter(fn($d) => $d->isExpired() || $d->isExpiringSoon())
            ->sortBy(fn($d) => $d->expiration_date);

        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dashboard', compact(
            'results',
            'recent',
            'documentTypes',
            'totalDocsCount',
            'activeDocsCount',
            'pendingDocsCount',
            'ownerExpiringDocs',
            'sharedExpiringDocs',
            'picExpiringDocs',
            'isPicUnitKerja'
        ));
    }
}
