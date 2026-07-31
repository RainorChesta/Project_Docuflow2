<x-app-layout>
    <x-slot name="header">Create User</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="form-control w-full mb-4">
                        <label for="name" class="label"><span class="label-text font-medium">Name</span></label>
                        <input type="text" name="name" id="name" class="input input-bordered w-full" required>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="email" class="label"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" id="email" class="input input-bordered w-full" required>
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
                                <option value="{{ $div->id }}">{{ $div->code }} - {{ $div->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="system_role" class="label"><span class="label-text font-medium">Role</span></label>
                        <select name="system_role" id="system_role" class="select select-bordered w-full" required>
                            <option value="user">User</option>
                            <option value="head">Division Head</option>
                            <option value="admin">System Admin</option>
                        </select>
                    </div>
                    <div class="form-control mb-4">
                        <label class="label cursor-pointer justify-start gap-3 px-0">
                            <input type="checkbox" name="is_active" value="1" checked class="checkbox checkbox-primary">
                            <span class="label-text">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
