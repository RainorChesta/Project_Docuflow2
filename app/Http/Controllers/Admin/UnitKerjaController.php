<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitKerjaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $search = trim((string) $request->get('search', ''));
        $query = UnitKerja::withCount(['documents', 'users'])->orderBy('kode_unit_kerja');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_unit_kerja', 'like', "%{$search}%")
                  ->orWhere('nama_unit_kerja', 'like', "%{$search}%");
            });
        }

        $unitKerjas = $query->paginate(20)->withQueryString();
        $totalUnitKerjas = UnitKerja::count();

        return view('admin.unit_kerja.index', compact('unitKerjas', 'search', 'totalUnitKerjas'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        return view('admin.unit_kerja.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'kode_unit_kerja' => 'required|string|max:50|unique:unit_kerjas,kode_unit_kerja',
            'nama_unit_kerja' => 'required|string|max:255',
        ], [
            'kode_unit_kerja.required' => __('Kode unit kerja wajib diisi.'),
            'kode_unit_kerja.unique' => __('Kode unit kerja sudah terdaftar di sistem.'),
            'nama_unit_kerja.required' => __('Nama unit kerja wajib diisi.'),
        ]);

        $validated['kode_unit_kerja'] = strtoupper(trim($validated['kode_unit_kerja']));

        UnitKerja::create($validated);

        return redirect()->route('admin.unit-kerja.index')
            ->with('success', __('Unit Kerja berhasil dibuat.'));
    }

    public function edit(UnitKerja $unitKerja): View
    {
        $this->authorize('admin');
        return view('admin.unit_kerja.edit', compact('unitKerja'));
    }

    public function update(Request $request, UnitKerja $unitKerja): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'kode_unit_kerja' => [
                'required',
                'string',
                'max:50',
                Rule::unique('unit_kerjas', 'kode_unit_kerja')->ignore($unitKerja->id),
            ],
            'nama_unit_kerja' => 'required|string|max:255',
        ], [
            'kode_unit_kerja.required' => __('Kode unit kerja wajib diisi.'),
            'kode_unit_kerja.unique' => __('Kode unit kerja sudah terdaftar di sistem.'),
            'nama_unit_kerja.required' => __('Nama unit kerja wajib diisi.'),
        ]);

        $validated['kode_unit_kerja'] = strtoupper(trim($validated['kode_unit_kerja']));

        $unitKerja->update($validated);

        return redirect()->route('admin.unit-kerja.index')
            ->with('success', __('Unit Kerja berhasil diperbarui.'));
    }

    public function destroy(UnitKerja $unitKerja): RedirectResponse
    {
        $this->authorize('admin');

        if ($unitKerja->documents()->exists() || $unitKerja->users()->exists()) {
            return back()->withErrors(['error' => __('Tidak dapat menghapus unit kerja yang memiliki pengguna atau dokumen terkait.')]);
        }

        $unitKerja->delete();

        return redirect()->route('admin.unit-kerja.index')
            ->with('success', __('Unit Kerja berhasil dihapus.'));
    }
}
