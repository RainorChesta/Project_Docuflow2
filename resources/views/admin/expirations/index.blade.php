<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-base-content/60 mb-1">
                    <a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">{{ __('Dasbor') }}</a>
                    <span>/</span>
                    <span class="text-base-content font-medium">{{ __('Expiration Notifications') }}</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <span>{{ __('Expiration Notifications') }}</span>
                </h1>
                <p class="text-sm text-base-content/60 mt-1">
                    {{ __('Kelola jadwal pengingat masa berlaku dokumen dan konfigurasi notifikasi otomatis untuk pemilik dokumen.') }}
                </p>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
                <a href="{{ route('admin.documents.index') }}" class="btn btn-outline btn-sm gap-2 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Semua Dokumen') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6" x-data="expirationManager()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
            <div class="alert alert-success shadow-xs rounded-2xl flex items-center justify-between border border-success/20">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="shrink-0 h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn btn-ghost btn-xs btn-circle" @click="$el.parentElement.remove()">✕</button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-error shadow-xs rounded-2xl flex items-center justify-between border border-error/20">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="shrink-0 h-5 w-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
                <button type="button" class="btn btn-ghost btn-xs btn-circle" @click="$el.parentElement.remove()">✕</button>
            </div>
            @endif

            {{-- GLOBAL CONFIGURATION CARD (Refined, Compact & Balanced) --}}
            <div class="card bg-base-100 border border-base-200 shadow-xs rounded-2xl overflow-hidden">
                <div class="card-body p-5 sm:p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        
                        {{-- Left: Description & Status --}}
                        <div class="space-y-2 max-w-xl">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-primary badge-sm font-semibold tracking-wide uppercase px-2.5 py-1 text-[10px]">{{ __('Global Configuration') }}</span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-primary/10 text-primary border border-primary/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                                    {{ __('Aktif:') }} {{ $defaultReminderDays }} {{ __('Hari') }} ({{ __('1 Bulan') }})
                                </span>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-bold text-base-content flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ __('Periode Notifikasi Default Dokumen Kedaluwarsa') }}</span>
                                </h2>
                                <p class="text-xs text-base-content/70 mt-1 leading-relaxed">
                                    {{ __('Tentukan jadwal standar sistem untuk mengirimkan notifikasi pengingat kepada pembuat dokumen sebelum masa berlaku berakhir. Dokumen yang tidak memiliki konfigurasi khusus akan otomatis menggunakan nilai default ini.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Right: Inline Form & Presets --}}
                        <div class="lg:w-96 shrink-0 bg-base-200/40 p-4 rounded-2xl border border-base-200 space-y-3">
                            <form method="POST" action="{{ route('admin.expirations.update-default') }}" class="space-y-2.5">
                                @csrf
                                @method('PUT')

                                <div class="flex items-center gap-2">
                                    <div class="join flex-1">
                                        <input type="number"
                                               name="default_reminder_days"
                                               id="default_reminder_days"
                                               min="1"
                                               max="365"
                                               value="{{ old('default_reminder_days', $defaultReminderDays) }}"
                                               required
                                               placeholder="30"
                                               class="input input-sm input-bordered join-item w-full font-semibold focus:border-primary text-xs bg-base-100">
                                        <span class="btn btn-sm btn-disabled join-item no-animation bg-base-200 text-base-content/70 text-xs font-medium border-base-300">
                                            {{ __('Hari Sebelum') }}
                                        </span>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm rounded-xl font-semibold gap-1.5 px-3.5 shrink-0 shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ __('Simpan') }}
                                    </button>
                                </div>

                                @error('default_reminder_days')
                                <p class="text-xs text-error">{{ $message }}</p>
                                @enderror

                                {{-- Quick Presets --}}
                                <div class="flex flex-wrap items-center gap-1 pt-0.5">
                                    <span class="text-[10px] text-base-content/50 uppercase font-bold tracking-wider mr-1">{{ __('Preset:') }}</span>
                                    <button type="button" class="btn btn-xs btn-ghost bg-base-100 hover:bg-base-200 text-base-content/70 rounded-lg font-medium text-[11px] px-2 border border-base-300/60" @click="setDefaultDays(7)">7h</button>
                                    <button type="button" class="btn btn-xs btn-ghost bg-base-100 hover:bg-base-200 text-base-content/70 rounded-lg font-medium text-[11px] px-2 border border-base-300/60" @click="setDefaultDays(14)">14h</button>
                                    <button type="button" class="btn btn-xs {{ $defaultReminderDays == 30 ? 'btn-primary text-primary-content' : 'btn-ghost bg-base-100 hover:bg-base-200 text-base-content/70 border border-base-300/60' }} rounded-lg font-semibold text-[11px] px-2" @click="setDefaultDays(30)">30h (1 Bln)</button>
                                    <button type="button" class="btn btn-xs btn-ghost bg-base-100 hover:bg-base-200 text-base-content/70 rounded-lg font-medium text-[11px] px-2 border border-base-300/60" @click="setDefaultDays(60)">60h (2 Bln)</button>
                                    <button type="button" class="btn btn-xs btn-ghost bg-base-100 hover:bg-base-200 text-base-content/70 rounded-lg font-medium text-[11px] px-2 border border-base-300/60" @click="setDefaultDays(90)">90h (3 Bln)</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SEARCH & FILTER BAR --}}
            <div class="card bg-base-100 border border-base-200 shadow-xs rounded-2xl">
                <div class="card-body p-4 sm:p-5">
                    <form method="GET" action="{{ route('admin.expirations.index') }}" class="space-y-3.5">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                            
                            {{-- Search Input --}}
                            <div class="lg:col-span-4">
                                <label class="input input-sm input-bordered flex items-center gap-2 rounded-xl focus-within:border-primary bg-base-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text"
                                           name="search"
                                           value="{{ request('search') }}"
                                           placeholder="{{ __('Cari judul, nomor dokumen, pembuat...') }}"
                                           class="grow text-xs" />
                                </label>
                            </div>

                            {{-- Status Filter --}}
                            <div class="lg:col-span-2">
                                <select name="status" class="select select-sm select-bordered w-full rounded-xl text-xs bg-base-100">
                                    <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>{{ __('Semua Status') }}</option>
                                    <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>{{ __('Segera Berakhir') }}</option>
                                    <option value="critical" {{ request('status') === 'critical' ? 'selected' : '' }}>{{ __('Kritis (≤ 7 Hari)') }}</option>
                                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('Telah Kedaluwarsa') }}</option>
                                    <option value="safe" {{ request('status') === 'safe' ? 'selected' : '' }}>{{ __('Masih Aman') }}</option>
                                </select>
                            </div>

                            {{-- Branch Filter --}}
                            <div class="lg:col-span-2">
                                <select name="branch_id" class="select select-sm select-bordered w-full rounded-xl text-xs bg-base-100">
                                    <option value="">{{ __('Semua Cabang') }}</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Unit Kerja Filter --}}
                            <div class="lg:col-span-2">
                                <select name="unit_kerja_id" class="select select-sm select-bordered w-full rounded-xl text-xs bg-base-100">
                                    <option value="">{{ __('Semua Unit Kerja') }}</option>
                                    @foreach($unitKerjas as $uk)
                                    <option value="{{ $uk->id }}" {{ request('unit_kerja_id') == $uk->id ? 'selected' : '' }}>{{ $uk->nama_unit_kerja }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Sort --}}
                            <div class="lg:col-span-2">
                                <select name="sort" class="select select-sm select-bordered w-full rounded-xl text-xs bg-base-100">
                                    <option value="expiration_asc" {{ request('sort', 'expiration_asc') === 'expiration_asc' ? 'selected' : '' }}>{{ __('Kedaluwarsa Terdekat') }}</option>
                                    <option value="expiration_desc" {{ request('sort') === 'expiration_desc' ? 'selected' : '' }}>{{ __('Kedaluwarsa Terjauh') }}</option>
                                    <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>{{ __('Judul (A-Z)') }}</option>
                                    <option value="created_desc" {{ request('sort') === 'created_desc' ? 'selected' : '' }}>{{ __('Dokumen Terbaru') }}</option>
                                </select>
                            </div>

                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-between pt-2 border-t border-base-200">
                            <div class="text-xs text-base-content/60">
                                {{ __('Menampilkan') }} <span class="font-bold text-base-content">{{ $documents->total() }}</span> {{ __('dokumen berjangka') }}
                            </div>
                            <div class="flex items-center gap-2">
                                @if(request()->hasAny(['search', 'status', 'branch_id', 'unit_kerja_id', 'document_type_id', 'sort']))
                                <a href="{{ route('admin.expirations.index') }}" class="btn btn-ghost btn-xs text-base-content/60 hover:text-base-content gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Reset') }}
                                </a>
                                @endif
                                <button type="submit" class="btn btn-primary btn-xs px-4 rounded-lg font-semibold gap-1.5 shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                                    {{ __('Terapkan Filter') }}
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            {{-- DOCUMENTS LIST TABLE --}}
            <div class="card bg-base-100 border border-base-200 shadow-xs rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full text-xs">
                        <thead>
                            <tr class="bg-base-200/60 text-base-content/70 font-semibold border-b border-base-200">
                                <th class="py-3 px-4">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pembuat / Pemilik') }}</th>
                                <th class="py-3 px-4">{{ __('Tanggal Kedaluwarsa') }}</th>
                                <th class="py-3 px-4">{{ __('Sisa Waktu') }}</th>
                                <th class="py-3 px-4">{{ __('Jadwal Pengingat') }}</th>
                                <th class="py-3 px-4">{{ __('Status Notifikasi') }}</th>
                                <th class="py-3 px-4 text-center">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @forelse($documents as $doc)
                            @php
                                $daysRemaining = $doc->daysUntilExpiration();
                                $isExpired = $doc->isExpired() || ($daysRemaining !== null && $daysRemaining < 0);
                                $isCritical = !$isExpired && $daysRemaining !== null && $daysRemaining <= 7;
                                $isExpiringSoon = !$isExpired && $doc->isExpiringSoon();
                                $customReminder = $doc->expiration_reminder_days;
                                $effectiveReminder = $doc->effective_reminder_days;
                            @endphp
                            <tr class="hover:bg-base-200/40 transition-colors group {{ $isExpired ? 'bg-error/[0.03]' : '' }}">
                                
                                {{-- Dokumen Title & Number --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-start gap-3 max-w-xs sm:max-w-sm">
                                        <div class="p-2 rounded-xl shrink-0 mt-0.5 {{ $isExpired ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/70' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-1">
                                            <a href="{{ route('documents.show', $doc) }}" class="font-bold text-sm text-base-content hover:text-primary transition-colors line-clamp-2 block leading-snug">
                                                {{ $doc->title }}
                                            </a>
                                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-base-content/60">
                                                <span class="font-mono bg-base-200/80 px-1.5 py-0.5 rounded border border-base-300/50 font-semibold">{{ $doc->document_number ?? '-' }}</span>
                                                @if($doc->documentType)
                                                <span>•</span>
                                                <span>{{ $doc->documentType->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Pembuat / Pemilik --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($doc->owner)
                                    <div class="flex items-center gap-2.5">
                                        <x-user-avatar :user="$doc->owner" size="w-7 h-7" text-size="text-xs" />
                                        <div>
                                            <div class="font-semibold text-base-content leading-tight">{{ $doc->owner->name }}</div>
                                            <div class="text-[11px] text-base-content/50">{{ $doc->owner->email }}</div>
                                            @if($doc->unitKerja || $doc->branch)
                                             <div class="text-[10px] text-base-content/40 font-medium">
                                                {{ $doc->unitKerja?->nama_unit_kerja ?? $doc->branch?->name }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @else
                                    <span class="text-base-content/40">-</span>
                                    @endif
                                </td>

                                {{-- Tanggal Kedaluwarsa --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-bold {{ $isExpired ? 'text-error' : 'text-base-content' }}">
                                        {{ $doc->expiration_date ? $doc->expiration_date->translatedFormat('d M Y') : '-' }}
                                    </div>
                                    <div class="text-[10px] {{ $isExpired ? 'text-error/70 font-medium' : 'text-base-content/40' }}">
                                        {{ $doc->expiration_date ? $doc->expiration_date->format('l') : '' }}
                                    </div>
                                </td>

                                {{-- Sisa Waktu (Red / Error for Expired, Primary/Accent for active) --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($isExpired)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-error/10 border border-error/25 text-error shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-error shrink-0 animate-pulse"></span>
                                        <span>{{ __('Kedaluwarsa') }} ({{ abs($daysRemaining ?? 0) }} {{ __('hari lalu') }})</span>
                                    </span>
                                    @elseif($isCritical)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-200 border border-base-300 text-base-content/90">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                                        <span>{{ $daysRemaining == 0 ? __('Hari ini') : ($daysRemaining == 1 ? __('Besok') : __(':days hari lagi', ['days' => $daysRemaining])) }}</span>
                                    </span>
                                    @elseif($isExpiringSoon)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-200 border border-base-300 text-base-content/80">
                                        <span class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                                        <span>{{ __(':days hari lagi', ['days' => $daysRemaining]) }}</span>
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-base-200/60 text-base-content/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/30 shrink-0"></span>
                                        <span>{{ __(':days hari lagi', ['days' => $daysRemaining]) }}</span>
                                    </span>
                                    @endif
                                </td>

                                {{-- Jadwal Pengingat --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if(!is_null($customReminder))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-primary/10 text-primary border border-primary/20">
                                        {{ __('Khusus:') }} {{ $customReminder }} {{ __('Hari') }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-base-200 text-base-content/60">
                                        {{ __('Default:') }} {{ $defaultReminderDays }} {{ __('Hari') }}
                                    </span>
                                    @endif
                                </td>

                                {{-- Status Notifikasi Terakhir --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        @if($doc->is_expiration_notified)
                                        <div class="flex items-center gap-1.5 text-xs font-medium {{ $isExpired ? 'text-error font-semibold' : 'text-base-content' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 {{ $isExpired ? 'text-error' : 'text-primary' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ __('Terkirim') }}</span>
                                        </div>
                                        @if($doc->expiration_notified_at)
                                        <div class="text-[10px] {{ $isExpired ? 'text-error/70' : 'text-base-content/50' }}">
                                            {{ $doc->expiration_notified_at->translatedFormat('d M Y, H:i') }}
                                        </div>
                                        @endif
                                        @else
                                        <div class="flex items-center gap-1.5 text-xs text-base-content/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-base-content/20"></span>
                                            <span>{{ __('Belum dikirim') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        
                                        {{-- Edit Reminder Settings Modal Button --}}
                                        <button type="button"
                                                class="btn btn-ghost btn-xs text-primary hover:bg-primary/10 gap-1 rounded-lg px-2 font-semibold"
                                                @click="openModal({{ json_encode([
                                                    'id' => $doc->id,
                                                    'title' => $doc->title,
                                                    'document_number' => $doc->document_number ?? '-',
                                                    'owner_name' => $doc->owner?->name ?? '-',
                                                    'expiration_date' => $doc->expiration_date ? $doc->expiration_date->format('Y-m-d') : '',
                                                    'expiration_date_formatted' => $doc->expiration_date ? $doc->expiration_date->translatedFormat('d M Y') : '-',
                                                    'days_remaining' => $daysRemaining,
                                                    'is_expired' => $isExpired,
                                                    'expiration_reminder_days' => $doc->expiration_reminder_days,
                                                    'use_default' => is_null($doc->expiration_reminder_days),
                                                    'update_url' => route('admin.expirations.update-document-reminder', $doc),
                                                    'notify_url' => route('admin.expirations.notify', $doc),
                                                ]) }})"
                                                title="{{ __('Atur Pengingat Dokumen Ini') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>{{ __('Atur') }}</span>
                                        </button>

                                        {{-- Instant Dispatch Notification Button --}}
                                        <button type="button"
                                                class="btn btn-ghost btn-xs {{ $isExpired ? 'text-error hover:bg-error/10' : 'text-base-content/70 hover:bg-base-200' }} gap-1 rounded-lg px-2 font-medium"
                                                @click="openNotifyModal({{ json_encode([
                                                    'id' => $doc->id,
                                                    'title' => $doc->title,
                                                    'document_number' => $doc->document_number ?? '-',
                                                    'owner_name' => $doc->owner?->name ?? 'Pembuat',
                                                    'owner_email' => $doc->owner?->email ?? '-',
                                                    'expiration_date' => $doc->expiration_date ? $doc->expiration_date->translatedFormat('d M Y') : '-',
                                                    'days_remaining' => $daysRemaining,
                                                    'is_expired' => $isExpired,
                                                    'notify_url' => route('admin.expirations.notify', $doc),
                                                ]) }})"
                                                title="{{ __('Kirim Notifikasi Pengingat Langsung') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                            </svg>
                                            <span>{{ __('Kirim') }}</span>
                                        </button>

                                        {{-- View Document --}}
                                        <a href="{{ route('documents.show', $doc) }}" class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content rounded-lg p-1.5" title="{{ __('Buka Dokumen') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </a>

                                    </div>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-base-content/50">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-base-200 flex items-center justify-center text-base-content/40">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="font-bold text-sm text-base-content">{{ __('Tidak ada dokumen berjangka yang ditemukan') }}</p>
                                        <p class="text-xs text-base-content/50 max-w-sm">
                                            {{ __('Dokumen yang dibuat tanpa menentukan tanggal kedaluwarsa bersifat permanen dan tidak akan masuk ke dalam daftar ini.') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($documents->hasPages())
                <div class="p-4 border-t border-base-200 bg-base-100 flex items-center justify-between">
                    {{ $documents->links() }}
                </div>
                @endif
            </div>

        </div>

        {{-- MODAL: Adjust Document Reminder Settings --}}
        <div class="modal" :class="{ 'modal-open': isModalOpen }" x-cloak>
            <div class="modal-box max-w-lg rounded-3xl p-6 relative border border-base-200 shadow-2xl bg-base-100">
                <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content" @click="closeModal()">✕</button>

                <div class="flex items-center gap-3 mb-5">
                    <div class="p-2.5 rounded-2xl bg-primary/10 text-primary shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content">{{ __('Atur Pengingat Dokumen') }}</h3>
                        <p class="text-xs text-base-content/60">{{ __('Konfigurasi notifikasi yang akan diterima pembuat dokumen.') }}</p>
                    </div>
                </div>

                <div class="bg-base-200/60 p-4 rounded-2xl border border-base-300/50 mb-4 space-y-2.5 text-xs">
                    <div class="space-y-1">
                        <div class="font-bold text-sm text-base-content line-clamp-1" x-text="activeDoc.title"></div>
                        <div class="flex items-center gap-2 text-xs text-base-content/60">
                            <span class="font-mono font-semibold" x-text="activeDoc.document_number"></span>
                            <span>•</span>
                            <span>{{ __('Pembuat:') }} <strong class="text-base-content" x-text="activeDoc.owner_name"></strong></span>
                        </div>
                    </div>

                    {{-- Fixed Expiration Date (Read-Only) --}}
                    <div class="pt-2 border-t border-base-200 flex items-center justify-between">
                        <span class="text-base-content/60 font-medium">{{ __('Tanggal Kedaluwarsa:') }}</span>
                        <div class="flex items-center gap-1.5 font-bold">
                            <span class="text-base-content" x-text="activeDoc.expiration_date_formatted || activeDoc.expiration_date || '-'"></span>
                            <template x-if="activeDoc.is_expired">
                                <span class="badge badge-error badge-xs text-white font-bold">{{ __('Kedaluwarsa') }}</span>
                            </template>
                            <template x-if="!activeDoc.is_expired && activeDoc.days_remaining !== null">
                                <span class="badge badge-neutral badge-xs font-semibold" x-text="activeDoc.days_remaining + ' Hari Lagi'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <form :action="activeDoc.update_url" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Hidden input for backend use_default_reminder boolean --}}
                    <input type="hidden" name="use_default_reminder" :value="activeDoc.reminder_mode === 'default' ? '1' : '0'">

                    {{-- Reminder Mode Selection --}}
                    <div class="space-y-2.5 pt-1">
                        <label class="label py-0.5">
                            <span class="label-text font-bold text-xs text-base-content">{{ __('Jadwal Notifikasi Pengingat Pembuat') }}</span>
                        </label>

                        {{-- Option 1: Use Global Default --}}
                        <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="activeDoc.reminder_mode === 'default' ? 'border-primary/60 bg-primary/5 ring-1 ring-primary/20' : 'border-base-200 hover:bg-base-200/40'"
                               @click="activeDoc.reminder_mode = 'default'">
                            <input type="radio"
                                   name="reminder_mode_radio"
                                   value="default"
                                   x-model="activeDoc.reminder_mode"
                                   class="radio radio-primary radio-sm mt-0.5 pointer-events-none" />
                            <div class="w-full">
                                <div class="font-semibold text-xs text-base-content">
                                    {{ __('Ikuti Pengaturan Default Global') }} ({{ $defaultReminderDays }} {{ __('Hari Sebelum Kedaluwarsa') }})
                                </div>
                                <div class="text-[11px] text-base-content/50 mt-0.5">
                                    {{ __('Notifikasi akan otomatis mengikuti perubahan setting default admin.') }}
                                </div>
                            </div>
                        </label>

                        {{-- Option 2: Custom Reminder Days --}}
                        <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition-all"
                               :class="activeDoc.reminder_mode === 'custom' ? 'border-primary/60 bg-primary/5 ring-1 ring-primary/20' : 'border-base-200 hover:bg-base-200/40'"
                               @click="activeDoc.reminder_mode = 'custom'">
                            <input type="radio"
                                   name="reminder_mode_radio"
                                   value="custom"
                                   x-model="activeDoc.reminder_mode"
                                   class="radio radio-primary radio-sm mt-0.5 pointer-events-none" />
                            <div class="w-full">
                                <div class="font-semibold text-xs text-base-content">
                                    {{ __('Tentukan Periode Pengingat Khusus Dokumen Ini') }}
                                </div>
                                <div class="text-[11px] text-base-content/50 mb-2 mt-0.5">
                                    {{ __('Atur jumlah hari khusus untuk mengirimkan peringatan kepada pembuat dokumen ini.') }}
                                </div>

                                {{-- Custom Days Input Field & Presets --}}
                                <div x-show="activeDoc.reminder_mode === 'custom'" x-cloak x-transition class="space-y-2 pt-1 border-t border-primary/10" @click.stop>
                                    <div class="space-y-1.5 pt-1">
                                        <div class="join w-full">
                                            <input type="number"
                                                   name="expiration_reminder_days"
                                                   x-model.number="activeDoc.expiration_reminder_days"
                                                   min="1"
                                                   :max="getMaxDays() !== null && getMaxDays() > 0 ? getMaxDays() : 365"
                                                   placeholder="Contoh: 15"
                                                   class="input input-bordered input-sm join-item w-full text-xs font-semibold focus:border-primary"
                                                   :class="{ 'input-error border-error text-error': isReminderExceeding() }">
                                            <span class="btn btn-sm btn-disabled join-item no-animation bg-base-200 text-base-content/70 text-xs font-medium">
                                                {{ __('Hari Sebelum') }}
                                            </span>
                                        </div>

                                        {{-- Maximum limit indicator / status info --}}
                                        <div class="flex items-center justify-between text-[11px] text-base-content/60 pt-0.5">
                                            <template x-if="activeDoc.expiration_date && getMaxDays() !== null && getMaxDays() > 0">
                                                <span class="flex items-center gap-1">
                                                    <span class="text-base-content/70">{{ __('Batas maksimum pengingat:') }}</span>
                                                    <span class="font-bold text-primary" x-text="getMaxDays() + ' Hari'"></span>
                                                    <span class="text-base-content/40">({{ __('sesuai sisa masa berlaku dokumen') }})</span>
                                                </span>
                                            </template>
                                            <template x-if="activeDoc.expiration_date && getMaxDays() !== null && getMaxDays() <= 0">
                                                <span class="text-error font-semibold flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                    {{ __('Dokumen telah kedaluwarsa atau berakhir hari ini.') }}
                                                </span>
                                            </template>
                                            <template x-if="!activeDoc.expiration_date">
                                                <span class="text-base-content/50 italic">{{ __('Dokumen tanpa tanggal kedaluwarsa (permanen).') }}</span>
                                            </template>
                                        </div>

                                        {{-- Real-time validation warning message --}}
                                        <div x-show="isReminderExceeding()" x-transition class="p-2.5 rounded-xl bg-error/10 border border-error/25 text-error text-xs font-medium flex items-start gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <div class="leading-snug">
                                                <span>{{ __('Periode pengingat tidak boleh melebihi sisa masa berlaku dokumen') }}</span>
                                                (<strong x-text="(getMaxDays() !== null ? Math.max(0, getMaxDays()) : 0) + ' Hari'"></strong>).
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Quick Presets --}}
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <span class="text-[10px] text-base-content/50 uppercase font-bold tracking-wider mr-0.5">{{ __('Preset:') }}</span>
                                        <button type="button"
                                                class="btn btn-xs rounded-lg font-medium transition-all"
                                                :class="activeDoc.expiration_reminder_days == 7 ? 'btn-primary text-primary-content' : 'btn-ghost bg-base-200/80 hover:bg-base-200 text-base-content/70'"
                                                :disabled="getMaxDays() !== null && getMaxDays() < 7"
                                                @click="activeDoc.expiration_reminder_days = 7">
                                            7 {{ __('Hari') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs rounded-lg font-medium transition-all"
                                                :class="activeDoc.expiration_reminder_days == 14 ? 'btn-primary text-primary-content' : 'btn-ghost bg-base-200/80 hover:bg-base-200 text-base-content/70'"
                                                :disabled="getMaxDays() !== null && getMaxDays() < 14"
                                                @click="activeDoc.expiration_reminder_days = 14">
                                            14 {{ __('Hari') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs rounded-lg font-medium transition-all"
                                                :class="activeDoc.expiration_reminder_days == 30 ? 'btn-primary text-primary-content' : 'btn-ghost bg-base-200/80 hover:bg-base-200 text-base-content/70'"
                                                :disabled="getMaxDays() !== null && getMaxDays() < 30"
                                                @click="activeDoc.expiration_reminder_days = 30">
                                            30 {{ __('Hari') }}
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs rounded-lg font-medium transition-all"
                                                :class="activeDoc.expiration_reminder_days == 60 ? 'btn-primary text-primary-content' : 'btn-ghost bg-base-200/80 hover:bg-base-200 text-base-content/70'"
                                                :disabled="getMaxDays() !== null && getMaxDays() < 60"
                                                @click="activeDoc.expiration_reminder_days = 60">
                                            60 {{ __('Hari') }}
                                        </button>
                                        
                                        <template x-if="getMaxDays() !== null && getMaxDays() > 0 && ![7, 14, 30, 60].includes(getMaxDays())">
                                            <button type="button"
                                                    class="btn btn-xs btn-outline btn-primary rounded-lg font-semibold text-[10px] px-2.5"
                                                    @click="activeDoc.expiration_reminder_days = getMaxDays()">
                                                <span x-text="'Maks. (' + getMaxDays() + ' Hari)'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="modal-action flex items-center justify-end gap-2 pt-3 border-t border-base-200">
                        <button type="button" class="btn btn-ghost btn-sm" @click="closeModal()">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit"
                                class="btn btn-primary btn-sm px-5 font-semibold gap-1.5 shadow-xs"
                                :disabled="isReminderExceeding()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Simpan Pengaturan') }}
                        </button>
                    </div>

                </form>

            </div>
        </div>

        {{-- MODAL: Confirm Direct Notification Dispatch --}}
        <div class="modal" :class="{ 'modal-open': isNotifyModalOpen }" x-cloak>
            <div class="modal-box max-w-md rounded-3xl p-6 relative border border-base-200 shadow-2xl bg-base-100">
                <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/50 hover:text-base-content" @click="closeNotifyModal()">✕</button>

                <div class="flex items-start gap-3.5 mb-5">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs"
                         :class="notifyDoc.is_expired ? 'bg-error/10 text-error ring-4 ring-error/5' : 'bg-primary/10 text-primary ring-4 ring-primary/5'">
                        <template x-if="notifyDoc.is_expired">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </template>
                        <template x-if="!notifyDoc.is_expired">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content"
                            x-text="notifyDoc.is_expired ? '{{ __('Kirim Notifikasi Dokumen Kedaluwarsa') }}' : '{{ __('Kirim Notifikasi Pengingat') }}'">
                        </h3>
                        <p class="text-xs text-base-content/60 mt-0.5">
                            {{ __('Kirimkan pemberitahuan peringatan langsung kepada pembuat / pemilik dokumen.') }}
                        </p>
                    </div>
                </div>

                {{-- Document & Recipient Detail Card --}}
                <div class="bg-base-200/60 p-4 rounded-2xl border border-base-300/50 mb-4 space-y-2.5 text-xs">
                    <div class="space-y-1">
                        <div class="font-bold text-sm text-base-content line-clamp-2 leading-snug" x-text="notifyDoc.title"></div>
                        <div class="text-[11px] text-base-content/60 font-mono" x-text="notifyDoc.document_number"></div>
                    </div>

                    <div class="pt-2 border-t border-base-200 flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('Penerima:') }}</span>
                        <span class="font-semibold text-base-content text-right">
                            <span x-text="notifyDoc.owner_name"></span>
                            <span class="text-base-content/50 font-normal block text-[10px]" x-text="notifyDoc.owner_email"></span>
                        </span>
                    </div>

                    <div class="pt-2 border-t border-base-200 flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('Tanggal Kedaluwarsa:') }}</span>
                        <div class="flex items-center gap-1.5 font-bold">
                            <span x-text="notifyDoc.expiration_date"></span>
                            <template x-if="notifyDoc.is_expired">
                                <span class="badge badge-error badge-xs text-white font-bold">{{ __('Kedaluwarsa') }}</span>
                            </template>
                            <template x-if="!notifyDoc.is_expired && notifyDoc.days_remaining !== null">
                                <span class="badge badge-neutral badge-xs font-semibold" x-text="notifyDoc.days_remaining + ' Hari Lagi'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Info Notice --}}
                <div class="p-3 rounded-2xl mb-4 text-xs flex items-start gap-2.5"
                     :class="notifyDoc.is_expired ? 'bg-error/10 text-error border border-error/20' : 'bg-primary/10 text-primary border border-primary/20'">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="leading-relaxed">
                        <template x-if="notifyDoc.is_expired">
                            <span>{{ __('Pemberitahuan peringatan kedaluwarsa berwarna merah akan dikirimkan ke lonceng notifikasi dan pop-up pembuat dokumen.') }}</span>
                        </template>
                        <template x-if="!notifyDoc.is_expired">
                            <span>{{ __('Notifikasi pengingat kedaluwarsa akan langsung terkirim dan tercatat pada riwayat pemberitahuan dokumen pembuat.') }}</span>
                        </template>
                    </div>
                </div>

                {{-- Action Form --}}
                <form :action="notifyDoc.notify_url" method="POST"
                      onsubmit="const btn = this.querySelector('button[type=submit]'); if(btn){ btn.disabled = true; btn.classList.add('opacity-75', 'cursor-not-allowed'); }">
                    @csrf
                    <div class="modal-action flex items-center justify-end gap-2 pt-3 border-t border-base-200 m-0">
                        <button type="button" class="btn btn-ghost btn-sm rounded-xl font-medium" @click="closeNotifyModal()">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit"
                                class="btn btn-sm rounded-xl px-5 font-semibold gap-1.5 shadow-xs"
                                :class="notifyDoc.is_expired ? 'btn-error text-white' : 'btn-primary'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span>{{ __('Kirim Sekarang') }}</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <script>
        function expirationManager() {
            return {
                isModalOpen: false,
                isNotifyModalOpen: false,
                activeDoc: {
                    id: null,
                    title: '',
                    document_number: '',
                    owner_name: '',
                    expiration_date: '',
                    expiration_date_formatted: '',
                    days_remaining: null,
                    is_expired: false,
                    expiration_reminder_days: 30,
                    use_default: true,
                    reminder_mode: 'default',
                    update_url: '',
                    notify_url: '',
                },
                notifyDoc: {
                    id: null,
                    title: '',
                    document_number: '',
                    owner_name: '',
                    owner_email: '',
                    expiration_date: '',
                    days_remaining: null,
                    is_expired: false,
                    notify_url: '',
                },
                setDefaultDays(days) {
                    var input = document.getElementById('default_reminder_days');
                    if (input) {
                        input.value = days;
                    }
                },
                getMaxDays() {
                    if (!this.activeDoc.expiration_date) {
                        return null;
                    }
                    var parts = this.activeDoc.expiration_date.split('-');
                    if (parts.length !== 3) return null;
                    var year = parseInt(parts[0], 10);
                    var month = parseInt(parts[1], 10) - 1;
                    var day = parseInt(parts[2], 10);
                    if (isNaN(year) || isNaN(month) || isNaN(day)) return null;

                    var expDate = new Date(year, month, day);
                    var now = new Date();
                    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

                    var diffTime = expDate.getTime() - today.getTime();
                    var diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays;
                },
                isReminderExceeding() {
                    if (this.activeDoc.reminder_mode === 'default') return false;
                    if (!this.activeDoc.expiration_date) return false;
                    var max = this.getMaxDays();
                    if (max === null) return false;
                    if (max <= 0) return true;
                    var val = parseInt(this.activeDoc.expiration_reminder_days, 10);
                    return isNaN(val) || val < 1 || val > max;
                },
                openModal(doc) {
                    this.activeDoc = Object.assign({}, doc);
                    this.activeDoc.reminder_mode = doc.use_default ? 'default' : 'custom';
                    var max = this.getMaxDays();
                    if (!this.activeDoc.expiration_reminder_days) {
                        if (max !== null && max > 0) {
                            this.activeDoc.expiration_reminder_days = Math.min({{ $defaultReminderDays }}, max);
                        } else {
                            this.activeDoc.expiration_reminder_days = {{ $defaultReminderDays }};
                        }
                    } else if (max !== null && max > 0 && this.activeDoc.expiration_reminder_days > max) {
                        this.activeDoc.expiration_reminder_days = max;
                    }
                    this.isModalOpen = true;
                },
                closeModal() {
                    this.isModalOpen = false;
                },
                openNotifyModal(doc) {
                    this.notifyDoc = Object.assign({}, doc);
                    this.isNotifyModalOpen = true;
                },
                closeNotifyModal() {
                    this.isNotifyModalOpen = false;
                }
            }
        }
    </script>
</x-app-layout>
