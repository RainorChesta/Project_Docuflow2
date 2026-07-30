<x-app-layout>
    <x-slot name="header">Divisions</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="mb-4">
                <a href="{{ route('admin.divisions.create') }}" class="btn btn-primary btn-sm">+ New Division</a>
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
                                <th>Code</th>
                                <th>Name</th>
                                <th>Users</th>
                                <th>Docs</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($divisions as $div)
                                <tr>
                                    <td class="font-mono">{{ $div->code }}</td>
                                    <td>{{ $div->name }}</td>
                                    <td>{{ $div->users_count }}</td>
                                    <td>{{ $div->documents_count }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.divisions.edit', $div) }}" class="link link-primary">Edit</a>
                                        @if($div->users_count === 0 && $div->documents_count === 0)
                                        <form method="POST" action="{{ route('admin.divisions.destroy', $div) }}" class="inline ml-2">
                                            @csrf @method('DELETE')
                                            <button class="link link-error">Delete</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($divisions->hasPages())
                    <div class="p-4 border-t border-base-200">{{ $divisions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
