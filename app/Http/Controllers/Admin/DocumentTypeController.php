<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = DocumentType::withCount('documents');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $documentTypes = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.document-types.index', compact('documentTypes', 'search', 'perPage'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        return view('admin.document-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:document_types,code',
            'name' => 'required|string|max:255',
        ]);

        DocumentType::create($validated);

        return redirect()->route('admin.document-types.index')->with('success', __('Tipe dokumen berhasil dibuat.'));
    }

    public function edit(DocumentType $documentType): View
    {
        $this->authorize('admin');
        return view('admin.document-types.edit', compact('documentType'));
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:document_types,code,' . $documentType->id,
            'name' => 'required|string|max:255',
        ]);

        $documentType->update($validated);

        return redirect()->route('admin.document-types.index')->with('success', __('Tipe dokumen berhasil diperbarui.'));
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        $this->authorize('admin');
        if ($documentType->documents()->exists()) {
            return back()->with('error', __('Tipe dokumen ini masih dipakai, tidak bisa dihapus.'));
        }

        $documentType->delete();

        return redirect()->route('admin.document-types.index')->with('success', __('Tipe dokumen berhasil dihapus.'));
    }
}
