<x-app-layout>
    <x-slot name="header">{{ __('Manajemen Tanda Tangan & Stempel') }}</x-slot>

    <div class="py-6"
         x-data="{
             openUsers: [],
             toggleUser(userId) {
                 if (this.openUsers.includes(userId)) {
                     this.openUsers = this.openUsers.filter(id => id !== userId);
                 } else {
                     this.openUsers.push(userId);
                 }
             },
             expandAll(allIds) {
                 this.openUsers = [...allIds];
             },
             collapseAll() {
                 this.openUsers = [];
             },
             // Quick Upload Modal State
             uploadModalOpen: false,
             uploadUserId: '',
             uploadUserName: '',
             uploadType: 'original',
             uploadCompanyId: '',
             openUploadModal(userId = '', userName = '', type = 'original', companyId = '') {
                 this.uploadUserId = userId;
                 this.uploadUserName = userName;
                 this.uploadType = type;
                 this.uploadCompanyId = companyId;
                 this.uploadModalOpen = true;
             },
             closeUploadModal() {
                 this.uploadModalOpen = false;
             },
             // Quick Edit Modal State
             editModalOpen: false,
             editActionUrl: '',
             editUserName: '',
             editTypeLabel: '',
             editCurrentUrl: '',
             openEditModal(actionUrl, userName, typeLabel, currentUrl) {
                 this.editActionUrl = actionUrl;
                 this.editUserName = userName;
                 this.editTypeLabel = typeLabel;
                 this.editCurrentUrl = currentUrl;
                 this.editModalOpen = true;
             },
             closeEditModal() {
                 this.editModalOpen = false;
             }
         }">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Metric Summary Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
                <div class="card bg-base-100 border border-base-300 shadow-sm p-4 hover:border-primary/40 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-medium text-base-content/60">{{ __('Total Pengguna') }}</div>
                            <div class="text-2xl font-bold text-base-content mt-1">{{ $stats['total_users'] }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-300 shadow-sm p-4 hover:border-success/40 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-medium text-base-content/60">{{ __('Punya TTD Asli') }}</div>
                            <div class="text-2xl font-bold text-success mt-1">{{ $stats['with_original'] }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-success/10 text-success flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-300 shadow-sm p-4 hover:border-secondary/40 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-medium text-base-content/60">{{ __('Punya Stempel Perusahaan') }}</div>
                            <div class="text-2xl font-bold text-secondary mt-1">{{ $stats['with_stamps'] }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-300 shadow-sm p-4 hover:border-warning/40 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs font-medium text-base-content/60">{{ __('Belum Ada TTD / Stempel') }}</div>
                            <div class="text-2xl font-bold text-warning mt-1">{{ $stats['missing_all'] }}</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-warning/10 text-warning flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter & Actions Toolbar --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    <form method="GET" action="{{ route('admin.signatures.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                        {{-- Search Input --}}
                        <div class="relative min-w-[220px] flex-1 max-w-sm">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('Cari nama, email, NIP pengguna...') }}"
                                   class="input input-bordered input-sm w-full pl-8 pr-7 text-xs rounded-lg" />
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        {{-- Status Filter --}}
                        <select name="status" onchange="this.form.submit()" class="select select-bordered select-sm text-xs rounded-lg">
                            <option value="">{{ __('Semua Status TTD') }}</option>
                            <option value="has_original" {{ request('status') === 'has_original' ? 'selected' : '' }}>{{ __('Punya TTD Asli') }}</option>
                            <option value="has_stamp" {{ request('status') === 'has_stamp' ? 'selected' : '' }}>{{ __('Punya Stempel Perusahaan') }}</option>
                            <option value="missing_original" {{ request('status') === 'missing_original' ? 'selected' : '' }}>{{ __('Belum Punya TTD Asli') }}</option>
                            <option value="no_signature" {{ request('status') === 'no_signature' ? 'selected' : '' }}>{{ __('Belum Ada TTD / Stempel') }}</option>
                        </select>

                        {{-- Company Filter --}}
                        <select name="company_id" onchange="this.form.submit()" class="select select-bordered select-sm text-xs rounded-lg">
                            <option value="">{{ __('Semua Perusahaan') }}</option>
                            @foreach($allCompanies as $comp)
                                <option value="{{ $comp->id }}" {{ (string)request('company_id') === (string)$comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} ({{ $comp->code }})
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm text-xs rounded-lg">{{ __('Filter') }}</button>

                        @if(request()->anyFilled(['search', 'status', 'company_id']))
                            <a href="{{ route('admin.signatures.index') }}" class="btn btn-ghost btn-sm text-xs rounded-lg">{{ __('Reset') }}</a>
                        @endif
                    </form>

                    <div class="flex items-center gap-2 justify-end shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-base-200">
                        {{-- Accordion toggle buttons --}}
                        @php $allPageUserIds = $users->pluck('id')->toJson(); @endphp
                        <button type="button"
                                @click="expandAll({{ $allPageUserIds }})"
                                class="btn btn-ghost btn-sm text-xs gap-1 hover:bg-base-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            {{ __('Buka Semua') }}
                        </button>

                        <button type="button"
                                @click="collapseAll()"
                                class="btn btn-ghost btn-sm text-xs gap-1 hover:bg-base-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                            {{ __('Tutup Semua') }}
                        </button>

                        {{-- Global Add Button --}}
                        <button type="button"
                                @click="openUploadModal('', '', 'original', '')"
                                class="btn btn-primary btn-sm gap-1.5 shadow-sm rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('Unggah TTD / Stempel') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Success / Error Alerts --}}
            @if(session('success'))
                <div class="alert alert-success text-sm py-2 shadow-sm rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error text-sm py-2 shadow-sm rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- User Accordion List --}}
            <div class="space-y-3">
                @forelse($users as $user)
                    @php
                        $userOriginalSig = $user->signatures->firstWhere('type', 'original');
                        $userStamps = $user->signatures->where('type', 'company_stamp');
                        $hasOriginal = !is_null($userOriginalSig);
                        $hasStamps = $userStamps->isNotEmpty();
                    @endphp

                    <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden transition-all duration-200 hover:border-primary/30">
                        {{-- Collapsed Accordion Header (Click to toggle dropdown) --}}
                        <div class="p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 cursor-pointer select-none hover:bg-base-200/30 transition-colors"
                             @click="toggleUser({{ $user->id }})">

                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="avatar shrink-0">
                                    <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-base border border-primary/20">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-bold text-sm text-base-content hover:text-primary transition-colors">{{ $user->name }}</h3>
                                        @if($user->nip)
                                            <span class="badge badge-ghost badge-xs font-mono text-base-content/60">{{ $user->nip }}</span>
                                        @endif
                                        @if($user->system_role)
                                            <span class="badge badge-outline badge-xs text-base-content/70 capitalize">{{ $user->system_role }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-base-content/60 truncate mt-0.5">{{ $user->email }}</div>
                                </div>
                            </div>

                            {{-- Status Badges & Action Trigger --}}
                            <div class="flex items-center gap-3 self-end md:self-center shrink-0">
                                {{-- Original Signature Status Badge --}}
                                <div class="flex items-center gap-1.5">
                                    @if($hasOriginal)
                                        <span class="badge badge-success badge-sm font-semibold text-white gap-1 shadow-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            {{ __('TTD Asli') }}
                                        </span>
                                    @else
                                        <span class="badge badge-ghost badge-sm text-base-content/40 font-medium">
                                            {{ __('Belum Ada TTD') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Company Stamps Status Badge --}}
                                <div class="flex items-center gap-1.5">
                                    @if($hasStamps)
                                        <span class="badge badge-secondary badge-sm font-semibold text-white gap-1 shadow-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $userStamps->count() }} {{ __('Stempel') }}
                                        </span>
                                    @else
                                        <span class="badge badge-ghost badge-sm text-base-content/40 font-medium">
                                            {{ __('0 Stempel') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Dropdown Chevron Icon --}}
                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center text-base-content/60 transition-transform duration-200"
                                     :class="openUsers.includes({{ $user->id }}) ? 'rotate-180 bg-primary/10 text-primary' : ''">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Expanded Dropdown Content (Information & Management) --}}
                        <div x-show="openUsers.includes({{ $user->id }})"
                             x-collapse
                             class="border-t border-base-200 bg-base-200/40 p-4 md:p-6 space-y-6">

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                                {{-- ===================================== --}}
                                {{-- 1. SECTION: TANDA TANGAN ASLI (ORIGINAL) --}}
                                {{-- ===================================== --}}
                                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-xl p-5 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between pb-3 border-b border-base-200">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </div>
                                                <h4 class="font-bold text-sm text-base-content">{{ __('Tanda Tangan Asli (Original)') }}</h4>
                                            </div>

                                            @if($hasOriginal)
                                                <span class="badge badge-success badge-sm text-white font-medium">{{ __('Aktif') }}</span>
                                            @else
                                                <span class="badge badge-ghost badge-sm text-base-content/50">{{ __('Belum Ada') }}</span>
                                            @endif
                                        </div>

                                        @if($hasOriginal)
                                            <div class="mt-4 flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                                {{-- Checkerboard transparent preview frame --}}
                                                <div class="w-32 h-32 shrink-0 rounded-xl border border-base-300 p-2 relative flex items-center justify-center shadow-inner overflow-hidden"
                                                     style="background-image: repeating-conic-gradient(#f3f4f6 0% 25%, #ffffff 0% 50%); background-size: 16px 16px;">
                                                    <img src="{{ $userOriginalSig->url }}" alt="Original Signature" class="max-w-full max-h-full object-contain drop-shadow-xs" />
                                                </div>

                                                <div class="flex-1 space-y-1.5 text-xs text-base-content/70 w-full sm:w-auto">
                                                    <div class="flex justify-between sm:justify-start sm:gap-4 py-1 border-b border-base-200">
                                                        <span class="text-base-content/50">{{ __('Metode Pembuatan:') }}</span>
                                                        <span class="font-semibold uppercase">{{ $userOriginalSig->created_via }}</span>
                                                    </div>
                                                    <div class="flex justify-between sm:justify-start sm:gap-4 py-1 border-b border-base-200">
                                                        <span class="text-base-content/50">{{ __('Tanggal Upload:') }}</span>
                                                        <span class="font-medium">{{ $userOriginalSig->created_at ? $userOriginalSig->created_at->format('d M Y, H:i') : '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between sm:justify-start sm:gap-4 py-1">
                                                        <span class="text-base-content/50">{{ __('Terakhir Update:') }}</span>
                                                        <span class="font-medium">{{ $userOriginalSig->updated_at ? $userOriginalSig->updated_at->format('d M Y, H:i') : '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Empty State --}}
                                            <div class="my-6 border-2 border-dashed border-base-300 rounded-xl p-6 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                <p class="text-xs text-base-content/60">{{ __('Pengguna ini belum memiliki tanda tangan asli di sistem.') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Actions footer for Original Signature --}}
                                    <div class="mt-5 pt-3 border-t border-base-200 flex items-center justify-end gap-2">
                                        @if($hasOriginal)
                                            <button type="button"
                                                    @click="openEditModal('{{ route('admin.signatures.update', $userOriginalSig) }}', '{{ addslashes($user->name) }}', 'Tanda Tangan Asli', '{{ $userOriginalSig->url }}')"
                                                    class="btn btn-ghost btn-xs text-primary gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                </svg>
                                                {{ __('Ganti Gambar') }}
                                            </button>

                                            <form action="{{ route('admin.signatures.destroy', $userOriginalSig) }}" method="POST"
                                                  onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus tanda tangan asli milik :name?', ['name' => $user->name]) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-xs text-error gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    {{ __('Hapus') }}
                                                </button>
                                            </form>
                                        @else
                                            <button type="button"
                                                    @click="openUploadModal('{{ $user->id }}', '{{ addslashes($user->name) }}', 'original', '')"
                                                    class="btn btn-primary btn-xs gap-1 shadow-xs rounded-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                {{ __('+ Unggah TTD Asli') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- ===================================== --}}
                                {{-- 2. SECTION: SIGNATURE + STEMPEL PERUSAHAAN --}}
                                {{-- ===================================== --}}
                                <div class="card bg-base-100 border border-base-300 shadow-sm rounded-xl p-5 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between pb-3 border-b border-base-200">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                    </svg>
                                                </div>
                                                <h4 class="font-bold text-sm text-base-content">{{ __('Signature + Stempel Perusahaan') }}</h4>
                                            </div>

                                            <span class="badge badge-secondary badge-sm text-white font-medium">
                                                {{ $userStamps->count() }} {{ __('Stempel') }}
                                            </span>
                                        </div>

                                        @if($hasStamps)
                                            <div class="mt-4 space-y-3 max-h-[320px] overflow-y-auto pr-1">
                                                @foreach($userStamps as $stamp)
                                                    <div class="flex items-center justify-between gap-3 p-2.5 rounded-xl border border-base-200 bg-base-200/30 hover:bg-base-200/60 transition-colors">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            {{-- Checkerboard transparent preview frame --}}
                                                            <div class="w-16 h-16 shrink-0 rounded-lg border border-base-300 p-1.5 flex items-center justify-center shadow-inner overflow-hidden"
                                                                 style="background-image: repeating-conic-gradient(#f3f4f6 0% 25%, #ffffff 0% 50%); background-size: 12px 12px;">
                                                                <img src="{{ $stamp->url }}" alt="Company Stamp" class="max-w-full max-h-full object-contain drop-shadow-xs" />
                                                            </div>

                                                            <div class="min-w-0 flex-1">
                                                                <div class="font-bold text-xs text-base-content truncate">{{ $stamp->company->name ?? __('Perusahaan Tidak Terdaftar') }}</div>
                                                                @if($stamp->company?->code)
                                                                    <span class="badge badge-ghost badge-xs font-mono mt-0.5">{{ $stamp->company->code }}</span>
                                                                @endif
                                                                <div class="text-[11px] text-base-content/50 mt-1">
                                                                    {{ __('Update:') }} {{ $stamp->updated_at ? $stamp->updated_at->format('d/m/Y') : '-' }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center gap-1 shrink-0">
                                                            <button type="button"
                                                                    @click="openEditModal('{{ route('admin.signatures.update', $stamp) }}', '{{ addslashes($user->name) }}', 'Stempel {{ addslashes($stamp->company->name ?? '') }}', '{{ $stamp->url }}')"
                                                                    class="btn btn-ghost btn-xs text-primary btn-square"
                                                                    title="{{ __('Ganti Gambar Stempel') }}">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                </svg>
                                                            </button>

                                                            <form action="{{ route('admin.signatures.destroy', $stamp) }}" method="POST"
                                                                  onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus stempel perusahaan ini?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-ghost btn-xs text-error btn-square" title="{{ __('Hapus Stempel') }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Empty State --}}
                                            <div class="my-6 border-2 border-dashed border-base-300 rounded-xl p-6 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                </svg>
                                                <p class="text-xs text-base-content/60">{{ __('Belum ada stempel perusahaan yang didaftarkan untuk pengguna ini.') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Actions footer for Company Stamp --}}
                                    <div class="mt-5 pt-3 border-t border-base-200 flex items-center justify-end">
                                        <button type="button"
                                                @click="openUploadModal('{{ $user->id }}', '{{ addslashes($user->name) }}', 'company_stamp', '')"
                                                class="btn btn-secondary btn-xs gap-1 shadow-xs rounded-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            {{ __('+ Tambah Stempel Perusahaan') }}
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card bg-base-100 border border-base-300 shadow-sm p-12 text-center rounded-2xl">
                        <div class="w-16 h-16 rounded-full bg-base-200 mx-auto flex items-center justify-center text-base-content/40 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-base text-base-content">{{ __('Tidak Ada Data Pengguna Ditemukan') }}</h3>
                        <p class="text-xs text-base-content/60 mt-1 max-w-sm mx-auto">
                            {{ __('Tidak ada pengguna yang cocok dengan kriteria pencarian atau filter yang Anda pilih.') }}
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
                <div class="p-4 bg-base-100 rounded-2xl border border-base-300 shadow-sm">
                    {{ $users->links() }}
                </div>
            @endif

        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL 1: UNGGAH TANDA TANGAN / STEMPEL (AJAX / Form)                      --}}
        {{-- ========================================================================= --}}
        <div class="modal modal-bottom sm:modal-middle" :class="uploadModalOpen ? 'modal-open' : ''">
            <div class="modal-box bg-base-100 max-w-md p-6 rounded-2xl border border-base-300 shadow-xl">
                <div class="flex items-center justify-between pb-3 border-b border-base-200">
                    <h3 class="font-bold text-base text-base-content">{{ __('Unggah Tanda Tangan / Stempel') }}</h3>
                    <button type="button" @click="closeUploadModal()" class="btn btn-ghost btn-xs btn-circle">✕</button>
                </div>

                <form action="{{ route('admin.signatures.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
                    @csrf

                    {{-- Target User --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('Pilih Pengguna') }}</span></label>
                        <select name="user_id" x-model="uploadUserId" class="select select-bordered select-sm w-full rounded-lg text-xs" required>
                            <option value="">{{ __('-- Pilih Pengguna --') }}</option>
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Signature Type --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('Tipe') }}</span></label>
                        <select name="type" x-model="uploadType" class="select select-bordered select-sm w-full rounded-lg text-xs" required>
                            <option value="original">{{ __('Tanda Tangan Asli (Original)') }}</option>
                            <option value="company_stamp">{{ __('Stempel Perusahaan') }}</option>
                        </select>
                    </div>

                    {{-- Company Select (Visible only if type === company_stamp) --}}
                    <div class="form-control" x-show="uploadType === 'company_stamp'" x-transition>
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('Perusahaan') }}</span></label>
                        <select name="company_id" x-model="uploadCompanyId" class="select select-bordered select-sm w-full rounded-lg text-xs" :required="uploadType === 'company_stamp'">
                            <option value="">{{ __('-- Pilih Perusahaan --') }}</option>
                            @foreach($allCompanies as $comp)
                                <option value="{{ $comp->id }}">{{ $comp->name }} ({{ $comp->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Image File Input --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('File Gambar (PNG / JPG)') }}</span></label>
                        <input type="file" name="signature_image" accept="image/png,image/jpeg" class="file-input file-input-bordered file-input-sm w-full rounded-lg text-xs" required />
                        <label class="label">
                            <span class="label-text-alt text-base-content/50 text-[11px]">{{ __('Maksimal 2MB. Sistem otomatis merapikan padding background.') }}</span>
                        </label>
                    </div>

                    <div class="modal-action pt-2 border-t border-base-200">
                        <button type="button" @click="closeUploadModal()" class="btn btn-ghost btn-sm text-xs rounded-lg">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm text-xs rounded-lg">{{ __('Simpan') }}</button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop bg-black/40 backdrop-blur-xs" @click="closeUploadModal()"></div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL 2: GANTI GAMBAR TANDA TANGAN / STEMPEL (Update)                     --}}
        {{-- ========================================================================= --}}
        <div class="modal modal-bottom sm:modal-middle" :class="editModalOpen ? 'modal-open' : ''">
            <div class="modal-box bg-base-100 max-w-md p-6 rounded-2xl border border-base-300 shadow-xl">
                <div class="flex items-center justify-between pb-3 border-b border-base-200">
                    <div>
                        <h3 class="font-bold text-base text-base-content">{{ __('Ganti Gambar') }}</h3>
                        <div class="text-xs text-base-content/60 mt-0.5">
                            <span x-text="editUserName"></span> — <span class="font-medium text-primary" x-text="editTypeLabel"></span>
                        </div>
                    </div>
                    <button type="button" @click="closeEditModal()" class="btn btn-ghost btn-xs btn-circle">✕</button>
                </div>

                <form :action="editActionUrl" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
                    @csrf
                    @method('PUT')

                    {{-- Current Preview --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('Gambar Saat Ini:') }}</span></label>
                        <div class="w-24 h-24 rounded-xl border border-base-300 p-2 flex items-center justify-center shadow-inner overflow-hidden mx-auto"
                             style="background-image: repeating-conic-gradient(#f3f4f6 0% 25%, #ffffff 0% 50%); background-size: 12px 12px;">
                            <img :src="editCurrentUrl" alt="Current Signature" class="max-w-full max-h-full object-contain drop-shadow-xs" />
                        </div>
                    </div>

                    {{-- New Replacement Image --}}
                    <div class="form-control">
                        <label class="label"><span class="label-text text-xs font-semibold">{{ __('Pilih Gambar Pengganti') }}</span></label>
                        <input type="file" name="signature_image" accept="image/png,image/jpeg" class="file-input file-input-bordered file-input-sm w-full rounded-lg text-xs" required />
                        <label class="label">
                            <span class="label-text-alt text-base-content/50 text-[11px]">{{ __('PNG/JPG, maks 2MB. Gambar lama akan otomatis digantikan.') }}</span>
                        </label>
                    </div>

                    <div class="modal-action pt-2 border-t border-base-200">
                        <button type="button" @click="closeEditModal()" class="btn btn-ghost btn-sm text-xs rounded-lg">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm text-xs rounded-lg">{{ __('Simpan Perubahan') }}</button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop bg-black/40 backdrop-blur-xs" @click="closeEditModal()"></div>
        </div>

    </div>
</x-app-layout>
