<x-app-layout>
    <x-slot name="header">Divisions</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('admin.divisions.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">+ New Division</a>
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
                                <th class="py-2">Code</th>
                                <th class="py-2">Name</th>
                                <th class="py-2">Users</th>
                                <th class="py-2">Docs</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($divisions as $div)
                                <tr class="border-b">
                                    <td class="py-2 font-mono">{{ $div->code }}</td>
                                    <td class="py-2">{{ $div->name }}</td>
                                    <td class="py-2">{{ $div->users_count }}</td>
                                    <td class="py-2">{{ $div->documents_count }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('admin.divisions.edit', $div) }}" class="text-blue-600 hover:underline">Edit</a>
                                        @if($div->users_count === 0 && $div->documents_count === 0)
                                        <form method="POST" action="{{ route('admin.divisions.destroy', $div) }}" class="inline ml-2">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $divisions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
