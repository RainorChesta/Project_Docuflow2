<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Division;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected VersionService $versionService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        // Tab selection: general | mine | division
        $type = $request->get('type', 'general');

        $query = Document::with('owner', 'division', 'currentVersion', 'versions');

        if ($type === 'general') {
            // General (public) — visible to everyone.
            $query->general();
        } elseif ($type === 'mine') {
            // My Documents — semua dokumen milik user, apapun scopenya.
            $query->ownedBy($user);
        } else {
            // Division-scoped documents of divisions the user belongs to.
            $query->division($user);
        }

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

        $view = match ($type) {
            'general' => 'documents.general',
            'mine' => 'documents.mine',
            default => 'documents.division',
        };

        return view($view, compact('documents', 'documentTypes', 'type'));
    }

    public function create(): View
    {
        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();
             $documentTypes = DocumentType::all(); 
        return view('documents.create', compact('divisions','documentTypes'));
    }

    /**
     * Preview nomor dokumen berikutnya untuk tipe dokumen tertentu, di
     * divisi milik user yang sedang login. Murni indikatif — nomor final
     * dihitung ulang (dengan lock) saat form benar-benar disubmit.
     */
    public function nextNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
        ]);

        $user = auth()->user();
        $division = $user->division_id ? Division::find($user->division_id) : null;
        $documentType = DocumentType::findOrFail($validated['document_type_id']);

        return response()->json([
            'number' => $this->documentService->previewNumber($division, $documentType),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $user = auth()->user();
        $isUpload = $request->boolean('is_upload');

        $rules = [
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
        ];

        if ($isUpload) {
            $rules['file'] = 'required|file|mimes:pdf,docx|max:10240';
            $rules['document_number'] = [
                'required',
                'string',
                'max:100',
                'unique:documents,document_number',
                // Format resmi: seq/tipe/divisi/pusat/bulan-romawi/tahun
                'regex:/^\d{3}\/[A-Z0-9.\-]+\/[A-Z0-9]+\/[A-Z0-9]+\/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)\/\d{4}$/',
            ];
        }

        $validated = $request->validate($rules, [
            'document_number.regex' => 'Format nomor tidak sesuai. Contoh: 029/S.ED/HRD/JBM/VIII/2026',
        ]);

        $validated['division_id'] = $user->division_id;
        $validated['visibility'] = Document::VISIBILITY_DIVISION;

        if ($isUpload) {
            $doc = $this->documentService->createFromUpload($validated, $user->id, $request->file('file'));
            $message = 'Dokumen diunggah. Menunggu approval kepala divisi.';
        } else {
            $doc = $this->documentService->create($validated, $user->id);
            $message = 'Document created. Fill in the content.';
        }

        $this->auditService->log($user, 'document.created', 'document', $doc->id, [
            'title' => $doc->title,
            'document_number' => $doc->document_number,
            'visibility' => $doc->visibility,
            'via_upload' => $isUpload,
        ]);

        return $isUpload
            ? redirect()->route('documents.show', $doc)->with('success', $message)
            : redirect()->route('documents.edit', $doc)->with('success', $message);
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'division', 'documentType', 'currentVersion', 'versions.author');

        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();

        return view('documents.show', compact('document', 'divisions'));
    }

    /**
     * Mulai ringkasan AI secara asinkron. Request langsung balas dengan
     * status processing — Groq dipanggil di queue job, bukan di sini.
     */
    public function summarize(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $force = $request->boolean('force');
        $percentage = (int) $request->input('percentage', 30);

        // Sudah selesai & tidak dipaksa ringkas ulang → kirim hasil yang tersimpan.
        if ($document->isSummaryCompleted() && !$force) {
            return response()->json([
                'status' => Document::SUMMARY_COMPLETED,
                'summary' => $document->summary,
            ]);
        }

        // Jika force ringkas ulang atau status sebelumnya failed → reset status
        if ($force || $document->summary_status === Document::SUMMARY_FAILED) {
            $document->update([
                'summary_status' => Document::SUMMARY_PENDING,
                'summary' => null,
                'summary_error' => null,
            ]);
        }

        $this->documentService->dispatchSummary($document, $percentage);

        $document->refresh();

        return response()->json([
            'status' => $document->summary_status,
            'summary' => $document->summary,
            'error' => $document->summary_error,
            'document_id' => $document->id,
        ]);
    }

    /**
     * Status ringkasan untuk polling frontend.
     */
    public function summaryStatus(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'status' => $document->summary_status,
            'summary' => $document->summary,
            'error' => $document->summary_error,
        ]);
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $document->load('currentVersion', 'versions');

        $latest = $document->versions()->latest('version_number')->first();

        abort_if(
            $latest && $latest->file_path,
            403,
            'Dokumen ini berasal dari unggahan berkas dan tidak bisa diedit lewat editor. Gunakan rollback untuk mengubah isinya.'
        );

        return view('documents.insert', compact('document'));
    }

    public function preview(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'division', 'documentType', 'currentVersion');

        return view('documents.preview', compact('document'));
    }

    public function previewContent(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('currentVersion');

        return view('documents.preview-content', compact('document'));
    }

    public function previewVersion(Document $document, DocumentVersion $version): View
    {
        $this->authorize('view', $document);

        abort_unless($version->document_id === $document->id, 404);

        return view('documents.preview-version', compact('document', 'version'));
    }

    public function save(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal',
            // paper_margin dikirim sebagai JSON string dari hidden input
            // (lihat insert.blade.php) — decode manual ke array.
            'paper_margin' => 'nullable|string',
        ]);

        $margin = $this->decodePaperMargin($validated['paper_margin'] ?? null);

        // Simpan pengaturan kertas ke dokumen (dipakai preview/show).
        $document->update([
            'paper_size' => $validated['paper_size'] ?? 'A4',
            'paper_margin' => $margin,
        ]);

        // savePending updates the existing pending version in place — no new version.
        $version = $this->versionService->savePending($document, $validated['content'], auth()->user());

        $this->auditService->log(auth()->user(), 'version.created', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        $message = $version->wasRecentlyCreated
            ? 'Edit saved. Pending approval.'
            : 'Versi v' . $version->version_number . ' diperbarui (tetap menunggu approval).';

        return redirect()->route('documents.show', $document)->with('success', $message);
    }

    public function discard(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $discarded = $this->versionService->discardPending($document);

        if ($discarded) {
            $this->auditService->log(auth()->user(), 'version.discarded', 'document_version', $discarded->id, [
                'document_id' => $document->id,
                'version_number' => $discarded->version_number,
            ]);
        }

        return redirect()->route('documents.index', ['type' => 'mine'])->with('success', $discarded
            ? 'Versi pending v' . $discarded->version_number . ' di-discard.'
            : 'Tidak ada versi pending untuk di-discard.');
    }

    public function saveDraft(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal',
            'paper_margin' => 'nullable|string',
        ]);

        $margin = $this->decodePaperMargin($validated['paper_margin'] ?? null);

        // Simpan pengaturan kertas ke dokumen (dipakai preview/show).
        // Hanya update yang benar-benar dikirim form — jangan menimpa
        // pengaturan yang sudah ada dengan null kalau form lama tidak
        // mengirim hidden input paper (dulu margin hilang setelah save draft).
        $document->update(array_filter([
            'paper_size' => $validated['paper_size'] ?? null,
            'paper_margin' => $margin,
        ], fn($v) => $v !== null));

        $this->versionService->saveDraft($document, $validated['content'], auth()->user());

        return redirect()->route('documents.show', $document)->with('success', 'Draft saved.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document discarded.');
    }

    public function uploadVersion(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $version = $this->versionService->savePendingFile($document, $request->file('file'), auth()->user());

        $this->auditService->log(auth()->user(), 'version.uploaded', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Versi baru diunggah. Menunggu approval.');
    }

    /**
     * Stream berkas unggahan secara privat. Akses tetap tunduk pada
     * policy 'view' dokumen — tidak pernah lewat disk publik.
     */
    public function file(Document $document, DocumentVersion $version)
    {
        $this->authorize('view', $document);

        abort_unless($version->document_id === $document->id, 404);
        abort_unless($version->file_path, 404);

        return Storage::disk('local')->response(
            $version->file_path,
            $version->file_original_name,
            ['Content-Disposition' => 'inline; filename="' . $version->file_original_name . '"']
        );
    }

    /**
     * Change a document's visibility scope (general / division / personal).
     * Division is not selectable here — the document keeps its original
     * division (division_id is NOT NULL at DB level).
     */
    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'visibility' => 'required|in:general,division,personal',
            'division_id' => 'nullable|required_if:visibility,division|exists:divisions,id',
        ]);

        $document->update([
            'visibility' => $validated['visibility'],
            // Legacy derived flag stays in sync with the scope.
            'is_public' => $validated['visibility'] === Document::VISIBILITY_GENERAL,
        ]);

        $this->auditService->log(auth()->user(), 'document.visibility_changed', 'document', $document->id, [
            'visibility' => $validated['visibility'],
            'division_id' => $document->division_id,
        ]);

        return back()->with('success', 'Document visibility updated.');
    }

    /**
     * Decode paper_margin yang dikirim sebagai JSON string dari hidden input
     * (lihat insert.blade.php). Kembalikan array {top,right,bottom,left} atau
     * null kalau kosong/tidak valid.
     */
    private function decodePaperMargin(?string $raw): ?array
    {
        if (!$raw || trim($raw) === '' || trim($raw) === 'null') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        // Hanya ambil 4 sisi margin, pastikan angka ≥ 0.
        $margin = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $v = $decoded[$side] ?? null;
            if (!is_numeric($v) || (int) $v < 0) {
                return null;
            }
            $margin[$side] = (int) $v;
        }

        return $margin;
    }
}