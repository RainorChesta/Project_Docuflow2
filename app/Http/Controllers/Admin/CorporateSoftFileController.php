<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CorporateSoftFile;
use App\Services\OnlyOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CorporateSoftFileController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $query = CorporateSoftFile::with(['creator', 'companies', 'branches']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($companyId = $request->get('company_id')) {
            $query->where(function ($q) use ($companyId) {
                $q->where('is_all_companies', true)
                  ->orWhereHas('companies', fn($cq) => $cq->where('companies.id', $companyId));
            });
        }

        if ($branchId = $request->get('branch_id')) {
            $query->where(function ($q) use ($branchId) {
                $q->where('is_all_branches', true)
                  ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $branchId));
            });
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $softFiles = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();
        $branches = Branch::with('company')->orderBy('name')->get();

        return view('admin.corporate_soft_files.index', compact('softFiles', 'companies', 'branches'));
    }

    public function create(): View
    {
        $this->authorize('admin');

        $companies = Company::with('branches')->orderBy('name')->get();
        $branches = Branch::with('company')->orderBy('name')->get();

        return view('admin.corporate_soft_files.create', compact('companies', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'file'             => ['required', 'file', 'max:15360', 'mimes:docx,doc,pdf', 'extensions:docx,doc,pdf'],
            'allowed_roles'    => 'nullable|array',
            'allowed_roles.*'  => 'in:direktur,head,staff,user',
            'is_all_companies' => 'nullable|boolean',
            'company_ids'      => 'nullable|array',
            'company_ids.*'    => 'exists:companies,id',
            'is_all_branches'  => 'nullable|boolean',
            'branch_ids'       => 'nullable|array',
            'branch_ids.*'     => 'exists:branches,id',
        ]);

        $isAllCompanies = $request->boolean('is_all_companies', false);
        $isAllBranches = $request->boolean('is_all_branches', false);

        // If no specific companies selected, default to all companies
        if (!$isAllCompanies && empty($validated['company_ids'])) {
            $isAllCompanies = true;
        }

        // If no specific branches selected, default to all branches
        if (!$isAllBranches && empty($validated['branch_ids'])) {
            $isAllBranches = true;
        }

        $file = $request->file('file');
        $storedPath = $file->store('corporate_soft_files', config('onlyoffice.storage_disk', 'local'));

        $softFile = CorporateSoftFile::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'file_path'          => $storedPath,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime'          => $file->getClientMimeType() ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size'          => $file->getSize(),
            'status'             => 'active',
            'allowed_roles'      => $validated['allowed_roles'] ?? null,
            'is_all_companies'   => $isAllCompanies,
            'is_all_branches'    => $isAllBranches,
            'created_by'         => auth()->id(),
        ]);

        if (!$isAllCompanies && !empty($validated['company_ids'])) {
            $softFile->companies()->sync($validated['company_ids']);
        }

        if (!$isAllBranches && !empty($validated['branch_ids'])) {
            $softFile->branches()->sync($validated['branch_ids']);
        }

        return redirect()->route('admin.corporate-soft-files.index')
            ->with('success', __('Soft File Korporat berhasil ditambahkan.'));
    }

    public function edit(CorporateSoftFile $corporateSoftFile): View
    {
        $this->authorize('admin');

        $companies = Company::with('branches')->orderBy('name')->get();
        $branches = Branch::with('company')->orderBy('name')->get();
        $corporateSoftFile->load('companies', 'branches');

        return view('admin.corporate_soft_files.edit', compact('corporateSoftFile', 'companies', 'branches'));
    }

    public function update(Request $request, CorporateSoftFile $corporateSoftFile): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'file'             => ['nullable', 'file', 'max:15360', 'mimes:docx,doc,pdf', 'extensions:docx,doc,pdf'],
            'allowed_roles'    => 'nullable|array',
            'allowed_roles.*'  => 'in:direktur,head,staff,user',
            'is_all_companies' => 'nullable|boolean',
            'company_ids'      => 'nullable|array',
            'company_ids.*'    => 'exists:companies,id',
            'is_all_branches'  => 'nullable|boolean',
            'branch_ids'       => 'nullable|array',
            'branch_ids.*'     => 'exists:branches,id',
        ]);

        $isAllCompanies = $request->boolean('is_all_companies', false);
        $isAllBranches = $request->boolean('is_all_branches', false);

        if (!$isAllCompanies && empty($validated['company_ids'])) {
            $isAllCompanies = true;
        }

        if (!$isAllBranches && empty($validated['branch_ids'])) {
            $isAllBranches = true;
        }

        $corporateSoftFile->title            = $validated['title'];
        $corporateSoftFile->description      = $validated['description'] ?? null;
        $corporateSoftFile->allowed_roles    = $validated['allowed_roles'] ?? null;
        $corporateSoftFile->is_all_companies = $isAllCompanies;
        $corporateSoftFile->is_all_branches  = $isAllBranches;

        // Replace file if new uploaded
        if ($request->hasFile('file')) {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

            if ($corporateSoftFile->file_path && $disk->exists($corporateSoftFile->file_path)) {
                $disk->delete($corporateSoftFile->file_path);
            }

            $file = $request->file('file');
            $corporateSoftFile->file_path          = $file->store('corporate_soft_files', config('onlyoffice.storage_disk', 'local'));
            $corporateSoftFile->file_original_name = $file->getClientOriginalName();
            $corporateSoftFile->file_mime          = $file->getClientMimeType() ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $corporateSoftFile->file_size          = $file->getSize();
        }

        $corporateSoftFile->save();

        if ($isAllCompanies) {
            $corporateSoftFile->companies()->detach();
        } else {
            $corporateSoftFile->companies()->sync($validated['company_ids'] ?? []);
        }

        if ($isAllBranches) {
            $corporateSoftFile->branches()->detach();
        } else {
            $corporateSoftFile->branches()->sync($validated['branch_ids'] ?? []);
        }

        return redirect()->route('admin.corporate-soft-files.index')
            ->with('success', __('Soft File Korporat berhasil diperbarui.'));
    }

    public function toggleStatus(CorporateSoftFile $corporateSoftFile): RedirectResponse
    {
        $this->authorize('admin');

        $corporateSoftFile->status = $corporateSoftFile->isActive() ? 'archived' : 'active';
        $corporateSoftFile->save();

        $label = $corporateSoftFile->isActive() ? __('diaktifkan') : __('dinonaktifkan / diarsipkan');

        return back()->with('success', __('Soft file korporat berhasil :status.', ['status' => $label]));
    }

    public function destroy(CorporateSoftFile $corporateSoftFile): RedirectResponse
    {
        $this->authorize('admin');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if ($corporateSoftFile->file_path && $disk->exists($corporateSoftFile->file_path)) {
            $disk->delete($corporateSoftFile->file_path);
        }

        $corporateSoftFile->delete();

        return redirect()->route('admin.corporate-soft-files.index')
            ->with('success', __('Soft File Korporat berhasil dihapus.'));
    }

    public function download(CorporateSoftFile $corporateSoftFile)
    {
        $this->authorize('admin');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if (!$disk->exists($corporateSoftFile->file_path)) {
            abort(404, 'Berkas soft file tidak ditemukan.');
        }

        return $disk->download($corporateSoftFile->file_path, $corporateSoftFile->file_original_name);
    }

    /**
     * Preview corporate soft file in a standalone page.
     */
    public function preview(CorporateSoftFile $corporateSoftFile, \App\Services\OnlyOfficeService $onlyOfficeService): View
    {
        $this->authorize('admin');

        $user = auth()->user();
        $onlyOfficeConfig = null;

        if (!$corporateSoftFile->isImage()) {
            $onlyOfficeConfig = $onlyOfficeService->generateCorporateSoftFileEditorConfig($corporateSoftFile, $user, 'view');
        }

        return view('admin.corporate_soft_files.preview', compact('corporateSoftFile', 'onlyOfficeConfig'));
    }

    /**
     * Serve inline content for preview (images, PDF, etc.).
     */
    public function previewContent(CorporateSoftFile $corporateSoftFile)
    {
        $this->authorize('admin');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if (!$corporateSoftFile->file_path || !$disk->exists($corporateSoftFile->file_path)) {
            abort(404, 'Berkas soft file tidak ditemukan di storage.');
        }

        $mime = $corporateSoftFile->file_mime ?? 'application/octet-stream';
        $fileName = $corporateSoftFile->file_original_name ?? basename($corporateSoftFile->file_path);

        return $disk->response($corporateSoftFile->file_path, $fileName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }

    /**
     * Return ONLYOFFICE preview config as JSON for modal viewer.
     */
    public function previewConfig(CorporateSoftFile $corporateSoftFile, \App\Services\OnlyOfficeService $onlyOfficeService): \Illuminate\Http\JsonResponse
    {
        $this->authorize('admin');

        $user = auth()->user();
        $config = $onlyOfficeService->generateCorporateSoftFileEditorConfig($corporateSoftFile, $user, 'view');

        return response()->json($config);
    }
}
