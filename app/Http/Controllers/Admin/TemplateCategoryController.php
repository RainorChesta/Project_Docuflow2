<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateCategoryController extends Controller
{
    public function index(): View
    {
        $categories = TemplateCategory::withCount('templates')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.template-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.template-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:template_categories,name',
        ]);

        TemplateCategory::create($validated);

        return redirect()->route('admin.template-categories.index')
            ->with('success', __('Kategori template berhasil dibuat.'));
    }

    public function edit(TemplateCategory $templateCategory): View
    {
        return view('admin.template-categories.edit', compact('templateCategory'));
    }

    public function update(Request $request, TemplateCategory $templateCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:template_categories,name,' . $templateCategory->id,
        ]);

        $templateCategory->update($validated);

        return redirect()->route('admin.template-categories.index')
            ->with('success', __('Kategori template berhasil diperbarui.'));
    }

    public function destroy(TemplateCategory $templateCategory): RedirectResponse
    {
        if ($templateCategory->templates()->exists()) {
            return back()->with('error', __('Kategori ini masih memiliki template, tidak bisa dihapus.'));
        }

        $templateCategory->delete();

        return redirect()->route('admin.template-categories.index')
            ->with('success', __('Kategori template berhasil dihapus.'));
    }
}
