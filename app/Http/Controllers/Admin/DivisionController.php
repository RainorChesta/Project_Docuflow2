<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(): View
    {
        $this->authorize('admin');
        $divisions = Division::withCount('users', 'documents')->paginate(20);
        return view('admin.divisions.index', compact('divisions'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        return view('admin.divisions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:divisions',
            'name' => 'required|string|max:255',
        ]);

        Division::create($validated);

        return redirect()->route('admin.divisions.index')->with('success', 'Division created.');
    }

    public function edit(Division $division): View
    {
        $this->authorize('admin');
        return view('admin.divisions.edit', compact('division'));
    }

    public function update(Request $request, Division $division): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:divisions,code,' . $division->id,
            'name' => 'required|string|max:255',
        ]);

        $division->update($validated);

        return redirect()->route('admin.divisions.index')->with('success', 'Division updated.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        $this->authorize('admin');
        if ($division->documents()->exists() || $division->users()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete division with associated documents or users.']);
        }
        $division->delete();
        return redirect()->route('admin.divisions.index')->with('success', 'Division deleted.');
    }
}
