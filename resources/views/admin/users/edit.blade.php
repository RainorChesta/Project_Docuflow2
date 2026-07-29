<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password (leave blank to keep)</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div class="mb-4">
                        <label for="division_id" class="block text-sm font-medium text-gray-700">Division</label>
                        <select name="division_id" id="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">None</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" {{ $user->division_id === $div->id ? 'selected' : '' }}>{{ $div->code }} - {{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="system_role" class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="system_role" id="system_role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="user" {{ $user->system_role === 'user' ? 'selected' : '' }}>User</option>
                            <option value="head" {{ $user->system_role === 'head' ? 'selected' : '' }}>Division Head</option>
                            <option value="admin" {{ $user->system_role === 'admin' ? 'selected' : '' }}>System Admin</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="rounded border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
