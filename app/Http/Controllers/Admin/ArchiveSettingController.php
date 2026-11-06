<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArchiveClassification;
use App\Models\ArchiveNumberingScheme;
use App\Models\ArchiveRetentionPolicy;
use App\Models\Company;
use App\Services\ArchiveNumberingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveSettingController extends Controller
{
    public function __construct(
        protected ArchiveNumberingService $numberingService
    ) {}

    /**
     * Archive configuration dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $tab = $request->input('tab', 'classifications'); // classifications, numbering, retention

        $classifications = ArchiveClassification::with(['parent', 'defaultRetentionPolicy', 'company'])
            ->orderBy('code')
            ->get();

        $numberingSchemes = ArchiveNumberingScheme::with(['company', 'creator'])
            ->latest('id')
            ->get();

        $retentionPolicies = ArchiveRetentionPolicy::with(['company', 'creator'])
            ->orderBy('code')
            ->get();

        $companies = Company::orderBy('name')->get();

        return view('admin.archives.settings', compact(
            'classifications',
            'numberingSchemes',
            'retentionPolicies',
            'companies',
            'tab'
        ));
    }

    /**
     * Store new classification.
     */
    public function storeClassification(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'parent_id' => 'nullable|exists:archive_classifications,id',
            'default_retention_policy_id' => 'nullable|exists:archive_retention_policies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'confidentiality_level' => 'required|in:public,internal,confidential,restricted',
            'requires_approval' => 'nullable|boolean',
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval', true);
        $validated['code'] = strtoupper(trim($validated['code']));

        ArchiveClassification::create($validated);

        return redirect()->route('admin.archive-settings.index', ['tab' => 'classifications'])
            ->with('success', __('Klasifikasi arsip baru berhasil ditambahkan.'));
    }

    /**
     * Store or update archive numbering scheme.
     */
    public function storeScheme(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:20',
            'template' => 'required|string|max:255',
            'sequence_scope' => 'required|in:company_year,branch_year,company_never',
            'reset_period' => 'required|in:yearly,monthly,never',
            'padding' => 'required|integer|min:3|max:10',
            'start_value' => 'required|integer|min:1',
            'timezone' => 'required|string|max:60',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = true;

        // Deactivate previous schemes for same company
        ArchiveNumberingScheme::where('company_id', $validated['company_id'] ?? null)
            ->update(['is_active' => false]);

        ArchiveNumberingScheme::create($validated);

        return redirect()->route('admin.archive-settings.index', ['tab' => 'numbering'])
            ->with('success', __('Skema penomoran arsip baru berhasil diaktifkan.'));
    }

    /**
     * Test render numbering scheme template via AJAX.
     */
    public function previewScheme(Request $request): JsonResponse
    {
        $this->authorize('admin');

        $template = $request->input('template', '{PUSAT}/{CLASS}/{ROMAN_MONTH}/{YYYY}/{SEQ:03}');
        $prefix = $request->input('prefix', 'JBM');
        $padding = (int) $request->input('padding', 3);
        $seq = (int) $request->input('start_value', 1);

        $tempScheme = new ArchiveNumberingScheme([
            'template' => $template,
            'prefix' => $prefix,
            'padding' => $padding,
            'timezone' => $request->input('timezone', 'Asia/Jakarta'),
        ]);

        $rendered = $this->numberingService->renderTemplate(
            scheme: $tempScheme,
            sequence: $seq,
            date: now(),
            companyCode: 'MMC',
            branchCode: 'PST',
            unitCode: 'SDM',
            classCode: 'S.ED',
            pusatCode: $prefix ?: 'JBM'
        );

        return response()->json(['sample' => $rendered]);
    }

    /**
     * Store retention policy / JRA.
     */
    public function storeRetentionPolicy(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_event' => 'required|in:registration_date,document_date,expiration_date,closure_date,manual_event',
            'active_retention_years' => 'required|integer|min:0|max:50',
            'inactive_retention_years' => 'required|integer|min:0|max:50',
            'final_disposition' => 'required|in:review,destroy,permanent',
            'requires_approval' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['total_retention_days'] = ($validated['active_retention_years'] + $validated['inactive_retention_years']) * 365;
        $validated['requires_approval'] = $request->boolean('requires_approval', true);
        $validated['created_by'] = $request->user()->id;

        ArchiveRetentionPolicy::create($validated);

        return redirect()->route('admin.archive-settings.index', ['tab' => 'retention'])
            ->with('success', __('Kebijakan retensi arsip (JRA) baru berhasil disimpan.'));
    }
}
