<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        $documentTypes = DocumentType::orderBy('name')->paginate(10);

        return view('admin.document-types.index', compact('documentTypes'));
    }

    public function create(): View
    {
        return view('admin.document-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:document_types,code',
            'name' => 'required|string|max:255',
        ]);

        DocumentType::create($validated);

        return redirect()->route('admin.document-types.index')->with('success', 'Document type created.');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('admin.document-types.edit', compact('documentType'));
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:document_types,code,' . $documentType->id,
            'name' => 'required|string|max:255',
        ]);

        $documentType->update($validated);

        return redirect()->route('admin.document-types.index')->with('success', 'Document type updated.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->documents()->exists()) {
            return back()->with('error', 'Tipe dokumen ini masih dipakai, tidak bisa dihapus.');
        }

        $documentType->delete();

        return redirect()->route('admin.document-types.index')->with('success', 'Document type deleted.');
    }
}
