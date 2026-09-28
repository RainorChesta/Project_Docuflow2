<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentController extends Controller
{
    /**
     * Halaman All Dokumen (Semua Dokumen) untuk role Admin.
     * Menampilkan semua dokumen secara langsung tanpa harus membuka folder bertingkat,
     * dilengkapi fitur filter, pencarian, checkbox seleksi, bulk download, dan bulk delete.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $search = $request->get('search');
        $selectedCompanyId = $request->get('company_id');
        $selectedBranchId = $request->get('branch_id');
        $selectedUnitKerjaId = $request->get('unit_kerja_id') ?? $request->get('division_id');
        $selectedDocTypeId = $request->get('document_type_id');
        $selectedStatus = $request->get('status');
        $selectedFormatChoice = $request->get('format_choice');
        $perPage = (int) $request->get('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Base Query
        $docQuery = Document::with([
            'owner',
            'unitKerja',
            'documentType',
            'branch.company',
            'company',
            'currentVersion',
            'versions',
        ]);

        // Search Filter (Judul, Nomor Dokumen, Nama Pemilik)
        if (!empty($search)) {
            $searchTrimmed = trim($search);
            $docQuery->where(function ($q) use ($searchTrimmed) {
                $q->where('title', 'like', "%{$searchTrimmed}%")
                  ->orWhere('document_number', 'like', "%{$searchTrimmed}%")
                  ->orWhereHas('owner', fn($uq) => $uq->where('name', 'like', "%{$searchTrimmed}%"));
            });
        }

        // Filter: Company
        if ($selectedCompanyId) {
            $docQuery->where(function ($q) use ($selectedCompanyId) {
                $q->where('company_id', $selectedCompanyId)
                  ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $selectedCompanyId));
            });
        }

        // Filter: Branch
        if ($selectedBranchId) {
            $docQuery->where('branch_id', $selectedBranchId);
        }

        // Filter: Unit Kerja
        if ($selectedUnitKerjaId) {
            $docQuery->where('unit_kerja_id', $selectedUnitKerjaId);
        }

        // Filter: Document Type
        if ($selectedDocTypeId) {
            $docQuery->where('document_type_id', $selectedDocTypeId);
        }

        // Filter: Format Choice (baru / lama)
        if ($selectedFormatChoice && in_array($selectedFormatChoice, ['baru', 'lama'], true)) {
            $docQuery->where('format_choice', $selectedFormatChoice);
        }

        // Filter: Status
        if ($selectedStatus) {
            if ($selectedStatus === 'expired') {
                $docQuery->where('is_expired', true);
            } elseif ($selectedStatus === 'active') {
                $docQuery->where('is_expired', false)
                    ->whereHas('currentVersion', fn($vq) => $vq->where('status', 'active'));
            } elseif ($selectedStatus === 'pending') {
                $docQuery->whereHas('versions', fn($vq) => $vq->where('status', 'pending'));
            } elseif ($selectedStatus === 'draft') {
                $docQuery->whereHas('versions', fn($vq) => $vq->where('status', 'draft'));
            } elseif ($selectedStatus === 'rejected') {
                $docQuery->whereHas('versions', fn($vq) => $vq->where('status', 'rejected'));
            }
        }

        // Calculate statistics for the overview banner
        $stats = [
            'total' => Document::count(),
            'active' => Document::where('is_expired', false)
                ->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
                ->count(),
            'pending' => Document::whereHas('versions', fn($q) => $q->where('status', 'pending'))->count(),
            'draft' => Document::whereHas('versions', fn($q) => $q->where('status', 'draft'))->count(),
            'expired' => Document::where('is_expired', true)->count(),
        ];

        // Paginate documents
        $documents = $docQuery->latest()->paginate($perPage)->withQueryString();

        // Dropdown options
        $companies = Company::orderBy('name')->get(['id', 'name', 'code']);
        
        $branchesQuery = Branch::orderBy('name');
        if ($selectedCompanyId) {
            $branchesQuery->where('company_id', $selectedCompanyId);
        }
        $branches = $branchesQuery->get(['id', 'name', 'company_id', 'code']);

        $unitKerjas = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja', 'kode_unit_kerja']);
        $documentTypes = DocumentType::orderBy('name')->get(['id', 'name', 'code', 'category']);

        return view('admin.documents.index', compact(
            'documents',
            'stats',
            'companies',
            'branches',
            'unitKerjas',
            'documentTypes',
            'search',
            'selectedCompanyId',
            'selectedBranchId',
            'selectedUnitKerjaId',
            'selectedDocTypeId',
            'selectedStatus',
            'selectedFormatChoice',
            'perPage'
        ));
    }

    /**
     * Tampilkan formulir edit informasi dokumen untuk role Admin.
     */
    public function edit(Document $document): View
    {
        $this->authorize('admin');

        $document->load(['owner', 'unitKerja', 'documentType', 'branch.company', 'company', 'currentVersion']);

        $companies = Company::orderBy('name')->get(['id', 'name', 'code']);
        $branches = Branch::orderBy('name')->get(['id', 'name', 'company_id', 'code']);
        $unitKerjas = UnitKerja::orderBy('nama_unit_kerja')->get(['id', 'nama_unit_kerja', 'kode_unit_kerja']);
        $documentTypes = DocumentType::orderBy('name')->get(['id', 'name', 'code', 'category']);

        return view('admin.documents.edit', compact(
            'document',
            'companies',
            'branches',
            'unitKerjas',
            'documentTypes'
        ));
    }

    /**
     * Simpan perubahan informasi dokumen langsung (tanpa perlu approval).
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'format_choice' => 'required|in:baru,lama',
            'document_number' => 'nullable|string|max:150',
            'document_type_id' => 'required|exists:document_types,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'visibility' => 'required|in:general,unit_kerja,personal',
            'expiration_date' => 'nullable|date',
            'paper_size' => 'nullable|string|max:20',
        ]);

        // Auto-assign company_id from branch if branch is selected
        if (!empty($validated['branch_id'])) {
            $branch = Branch::find($validated['branch_id']);
            if ($branch && $branch->company_id) {
                $validated['company_id'] = $branch->company_id;
            }
        }

        // Direct update on document
        $document->update([
            'title' => $validated['title'],
            'format_choice' => $validated['format_choice'],
            'document_number' => $validated['document_number'],
            'document_type_id' => $validated['document_type_id'],
            'company_id' => $validated['company_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'unit_kerja_id' => $validated['unit_kerja_id'] ?? null,
            'visibility' => $validated['visibility'],
            'expiration_date' => $validated['expiration_date'] ?? null,
            'paper_size' => $validated['paper_size'] ?? 'A4',
            // Clear pending rename request if any, because admin updated it directly
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        return redirect()->route('admin.documents.index')
            ->with('success', __('Informasi dokumen ":title" berhasil diperbarui secara langsung.', ['title' => $document->title]));
    }

    /**
     * Download satu dokumen (versi tampilan aktif / terakhir).
     */
    public function download(Request $request, Document $document)
    {
        $this->authorize('admin');

        $version = $request->filled('version_id')
            ? $document->versions()->where('id', $request->input('version_id'))->first()
            : $document->displayVersion();

        abort_unless($version && $version->file_path, 404, 'File not found');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        abort_unless($disk->exists($version->file_path), 404, 'Physical file not found');

        $downloadName = $version->file_original_name ?? $document->title;
        if (!str_ends_with(strtolower($downloadName), '.docx') && !str_ends_with(strtolower($downloadName), '.pdf')) {
            $downloadName .= '.docx';
        }

        return $disk->download($version->file_path, $downloadName);
    }

    /**
     * Bulk download beberapa dokumen sekaligus dalam format file ZIP.
     */
    public function bulkDownload(Request $request)
    {
        $this->authorize('admin');

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', __('Pilih setidaknya satu dokumen untuk diunduh.'));
        }

        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return back()->with('error', __('ID dokumen tidak valid.'));
        }

        $documents = Document::with(['versions', 'currentVersion'])
            ->whereIn('id', $ids)
            ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', __('Tidak ada dokumen yang ditemukan untuk diunduh.'));
        }

        // Buat temporary zip file
        $zipFileName = 'Dokumen_Export_' . now()->format('Ymd_His') . '_' . Str::random(6) . '.zip';
        $tempDir = storage_app_temp_dir();
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', __('Gagal membuat file ZIP arsip dokumen.'));
        }

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $addedCount = 0;
        $usedFileNames = [];

        foreach ($documents as $document) {
            $version = $document->displayVersion();
            if (!$version || !$version->file_path || !$disk->exists($version->file_path)) {
                continue;
            }

            $extension = pathinfo($version->file_path, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = str_ends_with(strtolower($version->file_original_name ?? ''), '.pdf') ? 'pdf' : 'docx';
            }

            // Sanitasi nama file dalam ZIP
            $baseTitle = Str::slug($document->title, '_');
            if (empty($baseTitle)) {
                $baseTitle = 'Dokumen_' . $document->id;
            }

            $docNumber = $document->document_number ? '_' . Str::slug($document->document_number, '-') : '';
            $entryName = "{$baseTitle}{$docNumber}.{$extension}";

            // Cegah duplikasi nama file dalam ZIP
            $counter = 1;
            $finalEntryName = $entryName;
            while (in_array(strtolower($finalEntryName), $usedFileNames, true)) {
                $finalEntryName = "{$baseTitle}{$docNumber}_({$counter}).{$extension}";
                $counter++;
            }
            $usedFileNames[] = strtolower($finalEntryName);

            $fullFilePath = $disk->path($version->file_path);
            $zip->addFile($fullFilePath, $finalEntryName);
            $addedCount++;
        }

        $zip->close();

        if ($addedCount === 0 || !file_exists($zipPath)) {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            return back()->with('error', __('Tidak ada berkas fisik dokumen yang dapat diunduh.'));
        }

        return response()->download($zipPath, 'Dokumen_Export_' . now()->format('Ymd_His') . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * Hapus satu dokumen (Pindahkan ke Sampah atau Hapus Permanen).
     */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('admin');

        $isForce = $request->boolean('force');

        if ($isForce) {
            $title = $document->title;
            $this->permanentlyDeleteDocument($document);
            return redirect()->route('admin.documents.index')
                ->with('success', __('Dokumen ":title" berhasil dihapus secara permanen.', ['title' => $title]));
        }

        $document->delete();

        return redirect()->route('admin.documents.index')
            ->with('success', __('Dokumen ":title" berhasil dipindahkan ke tempat sampah.', ['title' => $document->title]));
    }

    /**
     * Hapus beberapa dokumen sekaligus (Pindahkan ke Sampah atau Hapus Permanen).
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.documents.index')
                ->with('error', __('Pilih setidaknya satu dokumen untuk dihapus.'));
        }

        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return redirect()->route('admin.documents.index')
                ->with('error', __('ID dokumen yang dipilih tidak valid.'));
        }

        $isForce = $request->boolean('force');
        $documents = Document::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($documents as $document) {
            if ($isForce) {
                $this->permanentlyDeleteDocument($document);
            } else {
                $document->delete();
            }
            $count++;
        }

        if ($count === 0) {
            return redirect()->route('admin.documents.index')
                ->with('error', __('Tidak ada dokumen yang ditemukan untuk dihapus.'));
        }

        if ($isForce) {
            return redirect()->route('admin.documents.index')
                ->with('success', __(':count dokumen terpilih berhasil dihapus secara permanen.', ['count' => $count]));
        }

        return redirect()->route('admin.documents.index')
            ->with('success', __(':count dokumen terpilih berhasil dipindahkan ke tempat sampah.', ['count' => $count]));
    }

    /**
     * Helper untuk menghapus berkas fisik di disk dan menghapus record permanen.
     */
    private function permanentlyDeleteDocument(Document $document): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        foreach ($document->versions as $version) {
            if ($version->file_path && $disk->exists($version->file_path)) {
                $disk->delete($version->file_path);
            }
        }

        $document->versions()->delete();
        $document->forceDelete();
    }
}

/**
 * Helper internal untuk direktori temporary penyimpanan export ZIP
 */
function storage_app_temp_dir(): string
{
    return storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
}