<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="mb-4">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New User
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table min-w-[640px]">
                        <thead>
                            <tr>
                                <th>Name / NIP</th>
                                <th>Email / Phone</th>
                                <th>Division</th>
                                <th>Company & Cabang</th>
                                <th>Role</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>
                                        <div class="font-medium">{{ $user->name }}</div>
                                        @if($user->nip)
                                            <div class="text-xs text-base-content/50">NIP: {{ $user->nip }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $user->email }}</div>
                                        @if($user->phone_number)
                                            <div class="text-xs text-base-content/50">{{ $user->phone_number }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $user->division?->code ?? '-' }}</td>
                                    <td>
                                        @if($user->isDirector())
                                            <span class="badge badge-sm badge-info">{{ __('Semua PT & Cabang') }}</span>
                                        @else
                                            <div class="text-xs">
                                                <span class="font-semibold">{{ $user->companies->pluck('code')->join(', ') ?: '-' }}</span>
                                                <div class="text-base-content/60">{{ $user->branches->pluck('name')->join(', ') ?: '-' }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $user->system_role === 'admin' ? 'badge-accent' : ($user->system_role === 'direktur' ? 'badge-info' : ($user->system_role === 'head' ? 'badge-warning' : 'badge-ghost')) }} badge-sm uppercase font-semibold">
                                            {{ $user->system_role }}
                                        </span>
                                    </td>
                                    <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="link link-primary inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
