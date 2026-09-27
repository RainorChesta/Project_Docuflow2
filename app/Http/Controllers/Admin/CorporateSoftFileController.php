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

    public function store(Request $request, OnlyOfficeService $onlyOfficeService): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'paper_size'       => 'nullable|string|in:f4,a4',
            'file'             => ['required', 'file', 'max:15360', 'mimes:docx,doc,pdf', 'extensions:docx,doc,pdf'],
            'allowed_roles'    => 'nullable|array',
            'allowed_roles.*'  => 'in:direktur,head,staff,user',
            'is_all_companies' => 'nullable|boolean',
            'company_ids'      => 'nullable|array',
            'company_ids.*'    => 'exists:companies,id',
            'is_all_branches'  => 'nullable|boolean',
            'branch_ids'       => 'nullable|array',
            'branch_ids.*'     => 'exists:branches,id',
        ], [
            'file.required'   => __('Berkas soft file wajib diunggah.'),
            'file.mimes'      => __('Format gambar (JPEG/PNG) tidak diizinkan. Mohon gunakan berkas dokumen Microsoft Word (.docx) atau PDF (.pdf).'),
            'file.extensions' => __('Format gambar (JPEG/PNG) tidak diizinkan. Mohon gunakan berkas dokumen Microsoft Word (.docx) atau PDF (.pdf).'),
            'file.max'        => __('Ukuran berkas maksimal 15 MB.'),
            'paper_size.in'   => __('Pilihan ukuran kertas tidak valid.'),
        ]);

        $targetPaperSize = $validated['paper_size'] ?? 'f4';
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $rawBinary = file_get_contents($file->getRealPath());

        // Automatically convert uploaded file to target paper size (A4 or F4)
        $convertedBinary = $onlyOfficeService->convertFileToPaperSize($rawBinary, $ext, $targetPaperSize);

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $storedFileName = uniqid('csf_') . '.' . $ext;
        $storedPath = 'corporate_soft_files/' . $storedFileName;
        $disk->put($storedPath, $convertedBinary);

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

        $softFile = CorporateSoftFile::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'paper_size'         => $targetPaperSize,
            'file_path'          => $storedPath,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime'          => $file->getClientMimeType() ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size'          => strlen($convertedBinary),
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

        $paperLabel = $softFile->paper_size_label;
        return redirect()->route('admin.corporate-soft-files.index')
            ->with('success', __('Soft File Korporat berhasil ditambahkan dan otomatis dikonversi ke format :paper.', ['paper' => $paperLabel]));
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
            'paper_size'       => 'nullable|string|in:f4,a4',
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
        if (!empty($validated['paper_size'])) {
            $corporateSoftFile->paper_size   = $validated['paper_size'];
        }
        $corporateSoftFile->allowed_roles    = $validated['allowed_roles'] ?? null;
        $corporateSoftFile->is_all_companies = $isAllCompanies;
        $corporateSoftFile->is_all_branches  = $isAllBranches;

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

    /**
     * Inspect and strictly validate physical paper dimensions of uploaded file when target paper size is A4.
     * Rejects non-A4 files (F4, Letter, Legal, etc.) with a clear message showing actual dimensions.
     */
    protected function validatePaperSizeDimension(\Illuminate\Http\UploadedFile $file, string $targetPaperSize): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        if ($targetPaperSize === 'a4') {
            // Validate DOCX physical dimensions
            if (in_array($ext, ['docx', 'doc'])) {
                $zip = new \ZipArchive();
                if ($zip->open($realPath) === true) {
                    $docXml = $zip->getFromName('word/document.xml');
                    $zip->close();

                    if ($docXml !== false && preg_match('/<w:pgSz\b([^>]*)/i', $docXml, $matches)) {
                        $attrs = $matches[1];
                        $wTwips = null;
                        $hTwips = null;
                        if (preg_match('/\bw:w="(\d+)"/i', $attrs, $wm)) $wTwips = (int)$wm[1];
                        if (preg_match('/\bw:h="(\d+)"/i', $attrs, $hm)) $hTwips = (int)$hm[1];

                        if ($wTwips && $hTwips) {
                            if ($wTwips > $hTwips) {
                                $tmp = $wTwips; $wTwips = $hTwips; $hTwips = $tmp;
                            }

                            $wMm = (int)round($wTwips / 56.6929);
                            $hMm = (int)round($hTwips / 56.6929);

                            // A4 standard: 11906 x 16838 twips (210 x 297 mm)
                            // Reject if height is > 17500 (F4 is 18709 twips, Legal is 20160 twips)
                            // or height < 16100 (Letter is 15840 twips / 279mm)
                            if ($hTwips > 17500 || $hTwips < 16100) {
                                $detectedName = match (true) {
                                    $hTwips >= 18000 && $hTwips <= 19200 => 'F4 / Folio',
                                    $hTwips >= 15300 && $hTwips <= 16100 => 'Letter',
                                    $hTwips >= 19500 && $hTwips <= 20800 => 'Legal',
                                    $hTwips >= 23000 => 'A3',
                                    $hTwips <= 12500 => 'A5',
                                    default => 'Kustom / Non-A4',
                                };

                                return __("Gagal mengunggah: Berkas yang diunggah berukuran :detected (:w × :h mm), sedangkan softfile ini dikhususkan untuk format A4 (210 × 297 mm).", [
                                    'detected' => $detectedName,
                                    'w' => $wMm,
                                    'h' => $hMm,
                                ]);
                            }
                        }
                    }
                }
            }

            // Validate PDF physical dimensions
            if ($ext === 'pdf') {
                try {
                    $pdf = new \setasign\Fpdi\Fpdi();
                    $pageCount = $pdf->setSourceFile($realPath);
                    if ($pageCount > 0) {
                        $tpl = $pdf->importPage(1);
                        $size = $pdf->getTemplateSize($tpl);
                        $wPt = $size['width'];
                        $hPt = $size['height'];

                        if ($wPt > $hPt) {
                            $tmp = $wPt; $wPt = $hPt; $hPt = $tmp;
                        }

                        $wMm = (int)round($wPt * 0.352778);
                        $hMm = (int)round($hPt * 0.352778);

                        // A4 is 595.28 x 841.89 pt (210 x 297 mm)
                        // F4 is 595.28 x 935.43 pt (210 x 330 mm)
                        // Letter is 612 x 792 pt (215.9 x 279.4 mm)
                        if ($hPt > 875 || $hPt < 810) {
                            $detectedName = match (true) {
                                $hPt >= 900 && $hPt <= 970 => 'F4 / Folio',
                                $hPt >= 760 && $hPt <= 810 => 'Letter',
                                $hPt >= 980 && $hPt <= 1040 => 'Legal',
                                $hPt >= 1100 => 'A3',
                                $hPt <= 620 => 'A5',
                                default => 'Kustom / Non-A4',
                            };

                            return __("Gagal mengunggah: Berkas PDF yang diunggah berukuran :detected (:w × :h mm), sedangkan softfile ini dikhususkan untuk format A4 (210 × 297 mm).", [
                                'detected' => $detectedName,
                                'w' => $wMm,
                                'h' => $hMm,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('validatePaperSizeDimension PDF inspection warning: ' . $e->getMessage());
                }
            }
        }

        return null;
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
