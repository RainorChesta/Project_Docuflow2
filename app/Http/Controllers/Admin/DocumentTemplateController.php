<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $query = DocumentTemplate::with('documentType', 'creator')
            ->withCount('documents');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($documentTypeId = $request->get('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->paginate(15)->withQueryString();
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.templates.index', compact('templates', 'documentTypes'));
    }

    public function create(): View
    {
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.templates.create', compact('documentTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'document_type_id' => 'required|exists:document_types,id',
            'file'             => 'required|file|mimes:docx|max:10240',
        ]);

        $file = $request->file('file');

        $storedPath = $file->store('templates', config('onlyoffice.storage_disk', 'local'));

        DocumentTemplate::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'file_path'          => $storedPath,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime'          => $file->getClientMimeType(),
            'document_type_id'   => $validated['document_type_id'],
            'status'             => 'active',
            'created_by'         => auth()->id(),
        ]);

        return redirect()->route('admin.templates.index')
            ->with('success', __('Template berhasil diunggah.'));
    }

    public function createManual(): View
    {
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.templates.create-manual', compact('documentTypes'));
    }

    public function storeManual(Request $request, \App\Services\DocumentService $documentService): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'document_type_id' => 'required|exists:document_types,id',
        ]);

        $template = DocumentTemplate::create([
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'file_path'          => '', // Will be updated below
            'file_original_name' => $validated['title'] . '.docx',
            'file_mime'          => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'document_type_id'   => $validated['document_type_id'],
            'status'             => 'active',
            'created_by'         => auth()->id(),
        ]);

        // Create blank docx for the template
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906, // A4 in twips
            'pageSizeH' => 16838,
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);
        $section->addText(' ', ['name' => 'Arial', 'size' => 11]);

        $relativeFilePath = 'templates/' . $template->id . '_' . time() . '.docx';
        $tempPath = tempnam(sys_get_temp_dir(), 'docx_');
        
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        Storage::disk(config('onlyoffice.storage_disk', 'local'))
            ->put($relativeFilePath, file_get_contents($tempPath));

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $template->update(['file_path' => $relativeFilePath]);

        return redirect()->route('admin.templates.editor', $template)
            ->with('success', __('Template berhasil dibuat. Silakan edit isinya.'));
    }

    public function editor(DocumentTemplate $template, \App\Services\OnlyOfficeService $onlyOfficeService): View
    {
        $user = auth()->user();
        $onlyOfficeConfig = $onlyOfficeService->generateTemplateEditorConfig($template, $user, 'edit');

        return view('admin.templates.editor', compact('template', 'onlyOfficeConfig'));
    }

    public function edit(DocumentTemplate $template): View
    {
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.templates.edit', compact('template', 'documentTypes'));
    }

    public function update(Request $request, DocumentTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'document_type_id' => 'required|exists:document_types,id',
            'file'             => 'nullable|file|mimes:docx|max:10240',
        ]);

        $template->title            = $validated['title'];
        $template->description      = $validated['description'] ?? null;
        $template->document_type_id = $validated['document_type_id'];

        // Replace file if a new one is uploaded
        if ($request->hasFile('file')) {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

            // Delete old file
            if ($template->file_path && $disk->exists($template->file_path)) {
                $disk->delete($template->file_path);
            }

            $file = $request->file('file');
            $template->file_path          = $file->store('templates', config('onlyoffice.storage_disk', 'local'));
            $template->file_original_name = $file->getClientOriginalName();
            $template->file_mime          = $file->getClientMimeType();
        }

        $template->save();

        return redirect()->route('admin.templates.index')
            ->with('success', __('Template berhasil diperbarui.'));
    }

    public function destroy(DocumentTemplate $template): RedirectResponse
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if ($template->file_path && $disk->exists($template->file_path)) {
            $disk->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', __('Template berhasil dihapus.'));
    }

    /**
     * Toggle status between active and archived.
     */
    public function toggleStatus(DocumentTemplate $template): RedirectResponse
    {
        $template->status = $template->isActive() ? 'archived' : 'active';
        $template->save();

        $label = $template->isActive() ? __('diaktifkan') : __('diarsipkan');

        return back()->with('success', __('Template berhasil :status.', ['status' => $label]));
    }

    /**
     * Download the template file.
     */
    public function download(DocumentTemplate $template)
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if (!$disk->exists($template->file_path)) {
            abort(404, 'File template tidak ditemukan.');
        }

        return $disk->download($template->file_path, $template->file_original_name);
    }
}
