<x-app-layout>
    <x-slot name="header">Create User</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    @if($errors->any())
                        <div class="alert alert-error mb-4">
                            <ul class="text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="form-control w-full mb-4">
                        <label for="name" class="label"><span class="label-text font-medium">Name</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="email" class="label"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="password" class="label"><span class="label-text font-medium">Password</span></label>
                        <input type="password" name="password" id="password" class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="password_confirmation" class="label"><span class="label-text font-medium">Confirm Password</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="division_id" class="label"><span class="label-text font-medium">Division</span></label>
                        <select name="division_id" id="division_id" class="select select-bordered w-full">
                            <option value="">None</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>{{ $div->code }} - {{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="system_role" class="label"><span class="label-text font-medium">Role</span></label>
                        <select name="system_role" id="system_role" class="select select-bordered w-full" required>
                            <option value="user" {{ old('system_role', 'user') === 'user' ? 'selected' : '' }}>User</option>
                            <option value="head" {{ old('system_role') === 'head' ? 'selected' : '' }}>Division Head</option>
                            <option value="admin" {{ old('system_role') === 'admin' ? 'selected' : '' }}>System Admin</option>
                        </select>
                    </div>
                    <div class="form-control mb-4">
                        <label class="label cursor-pointer justify-start gap-3 px-0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="checkbox checkbox-primary">
                            <span class="label-text">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
