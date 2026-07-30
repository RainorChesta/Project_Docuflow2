<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">+ New User</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Division</th>
                                <th>Role</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->division?->code ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $user->system_role === 'admin' ? 'badge-accent' : ($user->system_role === 'head' ? 'badge-warning' : 'badge-ghost') }} badge-sm">
                                            {{ $user->system_role }}
                                        </span>
                                    </td>
                                    <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="link link-primary">Edit</a>
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
