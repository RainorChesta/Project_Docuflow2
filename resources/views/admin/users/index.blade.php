<x-app-layout>
    <x-slot name="header">Users</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">+ New User</a>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2">Name</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">Division</th>
                                <th class="py-2">Role</th>
                                <th class="py-2">Active</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr class="border-b">
                                    <td class="py-2">{{ $user->name }}</td>
                                    <td class="py-2">{{ $user->email }}</td>
                                    <td class="py-2">{{ $user->division?->code ?? '-' }}</td>
                                    <td class="py-2">
                                        <span class="px-2 py-0.5 rounded text-xs {{ $user->system_role === 'admin' ? 'bg-purple-100 text-purple-800' : ($user->system_role === 'head' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ $user->system_role }}
                                        </span>
                                    </td>
                                    <td class="py-2">{{ $user->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:underline">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
