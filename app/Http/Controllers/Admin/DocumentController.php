<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Semua dokumen dari semua divisi/user, termasuk yang masih pending
     * atau draft (belum di-approve). Admin bisa melihat & menghapusnya.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $query = Document::with('owner', 'division', 'documentType', 'currentVersion', 'versions');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhereHas('owner', fn($o) => $o->where('name', 'like', "%{$search}%"));
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
                $query->whereHas('versions', fn($q) => $q->where('status', 'pending'));
            } elseif ($status === 'draft') {
                $query->whereDoesntHave('versions', fn($q) => $q->where('status', '!=', 'draft'));
            }
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        $divisions = Division::orderBy('name')->get();
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.documents.index', compact('documents', 'divisions', 'documentTypes'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('admin');

        // Hapus berkas fisik semua versi sebelum record dihapus.
        foreach ($document->versions as $version) {
            if ($version->file_path) {
                Storage::disk('local')->delete($version->file_path);
            }
        }

        $document->delete();

        return redirect()->route('admin.documents.index')->with('success', 'Dokumen dihapus.');
    }
}