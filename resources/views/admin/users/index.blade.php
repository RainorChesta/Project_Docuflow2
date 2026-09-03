<x-app-layout>
    <x-slot name="header">{{ __('Pengguna') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ __('Pengguna Baru') }}
                </a>

                <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row flex-wrap gap-2 w-full md:w-auto justify-end">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari Nama, Email, NIP...') }}" class="input input-bordered input-sm w-full sm:w-auto" />
                    
                    <select name="division" class="select select-bordered select-sm w-full sm:w-auto">
                        <option value="">{{ __('Semua Divisi') }}</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" {{ request('division') == $division->id ? 'selected' : '' }}>
                                {{ $division->name ?? $division->code }}
                            </option>
                        @endforeach
                    </select>

                    <select name="role" class="select select-bordered select-sm w-full sm:w-auto">
                        <option value="">{{ __('Semua Peran') }}</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="direktur" {{ request('role') === 'direktur' ? 'selected' : '' }}>Direktur</option>
                        <option value="head" {{ request('role') === 'head' ? 'selected' : '' }}>Head</option>
                        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                    </select>

                    <select name="status" class="select select-bordered select-sm w-full sm:w-auto">
                        <option value="">{{ __('Semua Status') }}</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>

                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                        @if(request()->anyFilled(['search', 'division', 'role', 'status']))
                            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">{{ __('Reset') }}</a>
                        @endif
                    </div>
                </form>
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
                                <th>{{ __('Nama / NIP') }}</th>
                                <th>{{ __('Email / Telepon') }}</th>
                                <th>{{ __('Divisi') }}</th>
                                <th>{{ __('Perusahaan & Cabang') }}</th>
                                <th>{{ __('Peran') }}</th>
                                <th>{{ __('Aktif') }}</th>
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
                                        <div class="text-xs">
                                            <span class="font-semibold">{{ $user->companies->pluck('code')->join(', ') ?: '-' }}</span>
                                            <div class="text-base-content/60">{{ $user->branches->pluck('name')->join(', ') ?: '-' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $user->system_role === 'admin' ? 'badge-accent' : ($user->system_role === 'direktur' ? 'badge-info' : ($user->system_role === 'head' ? 'badge-warning' : 'badge-ghost')) }} badge-sm uppercase font-semibold">
                                            {{ $user->system_role }}
                                        </span>
                                    </td>
                                    <td>{{ $user->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="text-right whitespace-nowrap">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-xs btn-square text-primary" title="{{ __('Edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>
                                        @if(auth()->id() !== $user->id)
                                            <button type="button" onclick="document.getElementById('delete-user-modal-{{ $user->id }}').showModal()" class="btn btn-ghost btn-xs btn-square text-error" title="{{ __('Hapus User') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>

                                            {{-- Delete Confirmation Modal --}}
                                            <dialog id="delete-user-modal-{{ $user->id }}" class="modal text-left whitespace-normal">
                                                <div class="modal-box">
                                                    <h3 class="font-bold text-lg text-error flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                        </svg>
                                                        {{ __('Konfirmasi Hapus Permanen User') }}
                                                    </h3>
                                                    <p class="py-4 text-sm text-base-content/80">
                                                        {{ __('Apakah Anda yakin ingin menghapus user') }} <span class="font-semibold text-base-content">{{ $user->name }}</span> ({{ $user->email }}) {{ __('secara permanen? Semua data terkait (tanda tangan, akses, dokumen yang dibuat) akan ikut terhapus dan tindakan ini tidak dapat dibatalkan.') }}
                                                    </p>
                                                    <div class="modal-action flex justify-end gap-2">
                                                        <button type="button" onclick="document.getElementById('delete-user-modal-{{ $user->id }}').close()" class="btn btn-ghost">
                                                            {{ __('Batal') }}
                                                        </button>
                                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-error">
                                                                {{ __('Hapus Permanen') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('close') }}</button>
                                                </form>
                                            </dialog>
                                        @endif
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
