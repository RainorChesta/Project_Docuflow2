<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('admin');
        $users = User::with('division')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        $divisions = Division::all();
        return view('admin.users.create', compact('divisions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'division_id' => 'nullable|exists:divisions,id',
            'system_role' => 'required|in:admin,head,user',
            'is_active' => 'boolean',
        ]);

        // password di-hash otomatis oleh cast 'hashed' di model
        unset($validated['password_confirmation']);

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('admin');
        $divisions = Division::all();
        return view('admin.users.edit', compact('user', 'divisions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'division_id' => 'nullable|exists:divisions,id',
            'system_role' => 'required|in:admin,head,user',
            'is_active' => 'boolean',
        ]);

        // password di-hash otomatis oleh cast 'hashed' di model
        unset($validated['password_confirmation']);
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('admin');
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Cannot delete yourself.']);
        }
        $user->update(['is_active' => false]);
        return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
    }
}
