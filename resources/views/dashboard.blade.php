<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    <div class="pb-8">
        <div class="max-w-7xl mx-auto w-full">
            @if(auth()->user()->isAdmin() || auth()->user()->isDirector())
                {{-- Modern Executive & Admin Analytics Dashboard inspired by SaaS Dashboard aesthetics --}}
                <div class="space-y-6">
                    
                    {{-- Header / Welcome Banner --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-base-content flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                </div>
                                <span>{{ auth()->user()->isDirector() ? __('Dashboard Eksekutif') : __('Dashboard Analitik') }}</span>
                            </h2>
                            <p class="text-xs sm:text-sm text-base-content/60 mt-1">
                                {{ auth()->user()->isDirector() ? __('Pemantauan naskah terbit, tembusan pimpinan, dan tingkat kepatuhan organisasi.') : __('Ringkasan performa, tren penerbitan naskah, tingkat kepatuhan, dan distribusi dokumen organisasi.') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5 shrink-0 self-start sm:self-auto flex-wrap sm:flex-nowrap">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.documents.index') }}" 
                                   class="btn btn-sm h-10 px-3.5 rounded-xl bg-base-100 hover:bg-base-200 border border-base-300 text-base-content/80 hover:text-primary font-semibold gap-2 shadow-2xs transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>{{ __('Semua Dokumen') }}</span>
                                </a>
                                <a href="{{ route('documents.choose') }}" 
                                   class="btn btn-sm h-10 px-4 rounded-xl bg-primary hover:bg-primary/90 text-white font-semibold gap-2 shadow-xs border-0 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>{{ __('Buat Dokumen') }}</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Top Stat Cards (Theme Native Tokens for Flawless Light & Dark Mode) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        {{-- Total Dokumen --}}
                        <div class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-primary/50 transition-all group">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider">{{ __('Total Dokumen') }}</span>
                                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary border border-primary/20 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <div class="text-2xl sm:text-3xl font-black text-base-content tracking-tight">
                                    {{ number_format($totalDocsCount) }}
                                </div>
                                <span class="inline-flex items-center gap-0.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary/10 text-primary border border-primary/20 shadow-2xs">
                                    <span>{{ $totalUnitKerjaCount }}</span>
                                    <span class="font-normal">{{ __('Unit') }}</span>
                                </span>
                            </div>
                            <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center gap-1">
                                <span>{{ __('Seluruh arsip organisasi') }}</span>
                            </div>
                        </div>

                        {{-- Dokumen Aktif (Disetujui) --}}
                        @if(auth()->user()->isDirector())
                            <a href="{{ route('director.active-documents.index') }}" class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-success/50 hover:shadow-md transition-all group block">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider group-hover:text-success transition-colors">{{ __('Dokumen Aktif') }}</span>
                                    <div class="w-9 h-9 rounded-xl bg-success/10 text-success border border-success/20 flex items-center justify-center shadow-2xs group-hover:scale-105 group-hover:bg-success group-hover:text-white transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-end justify-between">
                                    <div class="text-2xl sm:text-3xl font-black text-success tracking-tight">
                                        {{ number_format($activeDocsCount) }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-success/10 text-success border border-success/20 shadow-2xs">
                                        {{ $complianceRate }}%
                                    </span>
                                </div>
                                <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center justify-between">
                                    <span>{{ __('Tinjau Dokumen Terbit') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 group-hover:text-success group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @else
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-success/50 transition-all group">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider">{{ __('Dokumen Aktif') }}</span>
                                    <div class="w-9 h-9 rounded-xl bg-success/10 text-success border border-success/20 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-end justify-between">
                                    <div class="text-2xl sm:text-3xl font-black text-success tracking-tight">
                                        {{ number_format($activeDocsCount) }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-success/10 text-success border border-success/20 shadow-2xs">
                                        {{ $complianceRate }}%
                                    </span>
                                </div>
                                <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center gap-1">
                                    <span>{{ __('Disetujui & siap digunakan') }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Card 3: Tembusan Direktur (untuk Direktur) / Menunggu Review (untuk Admin) --}}
                        @if(auth()->user()->isDirector())
                            <a href="{{ route('director.documents.index', ['tab' => 'tembusan']) }}" class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-warning/50 transition-all group block">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider">{{ __('Tembusan Direktur') }}</span>
                                    <div class="w-9 h-9 rounded-xl bg-warning/10 text-warning border border-warning/20 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-end justify-between">
                                    <div class="text-2xl sm:text-3xl font-black text-warning tracking-tight">
                                        {{ number_format($unreadTembusanCount) }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-warning/10 text-warning border border-warning/20 shadow-2xs">
                                        {{ $unreadTembusanCount > 0 ? __('Belum Dibaca') : __('Semua Terbaca') }}
                                    </span>
                                </div>
                                <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center justify-between">
                                    <span>{{ __('Naskah tembusan masuk') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @else
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-warning/50 transition-all group">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider">{{ __('Menunggu Review') }}</span>
                                    <div class="w-9 h-9 rounded-xl bg-warning/10 text-warning border border-warning/20 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-end justify-between">
                                    <div class="text-2xl sm:text-3xl font-black text-warning tracking-tight">
                                        {{ number_format($pendingDocsCount) }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-warning/10 text-warning border border-warning/20 shadow-2xs">
                                        {{ __('Review') }}
                                    </span>
                                </div>
                                <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center gap-1">
                                    <span>{{ __('Antrean persetujuan pimpinan') }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- Dokumen Hampir Kedaluwarsa --}}
                        <div class="bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:border-error/50 transition-all cursor-pointer group"
                             onclick="document.getElementById('admin-expiring-modal').showModal()"
                             title="{{ __('Klik untuk melihat daftar dokumen') }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-base-content/70 uppercase tracking-wider">{{ __('Retensi & Kedaluwarsa') }}</span>
                                <div class="w-9 h-9 rounded-xl bg-error/10 text-error border border-error/20 flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-end justify-between">
                                <div class="text-2xl sm:text-3xl font-black text-error tracking-tight">
                                    {{ number_format($expiringDocuments->count()) }}
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-error/10 text-error border border-error/20 shadow-2xs gap-1">
                                    <span>≤ 30 {{ __('Hari') }}</span>
                                </span>
                            </div>
                            <div class="text-[11.5px] text-base-content/60 mt-2 flex items-center justify-between">
                                <span>{{ __('Perlu peninjauan berkala') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>

                    </div>

                    {{-- Middle Row: Main Spline Chart + Monthly Target Gauge + Top Categories Donut --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        
                        {{-- Main Analytics & Target (Span 8 Cols) --}}
                        <div class="lg:col-span-8 space-y-5">
                            
                            {{-- Spline Line/Area Chart: Activity Analytics --}}
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-5 shadow-xs space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                            <span>{{ __('Analisis Aktivitas Dokumen') }}</span>
                                        </h3>
                                        <p class="text-xs text-base-content/50 mt-0.5">
                                            {{ __('Perbandingan pembuatan naskah baru dan dokumen yang disetujui.') }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 self-start sm:self-auto">
                                        <span class="badge badge-sm badge-primary font-semibold border-0 px-2.5 py-1 text-xs">
                                            {{ __('8 Hari Terakhir') }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Chart Container --}}
                                <div class="w-full">
                                    <div id="chart-document-analytics" class="w-full h-[280px]"></div>
                                </div>

                                {{-- Chart Legends Footer --}}
                                <div class="flex items-center justify-center gap-6 pt-2 border-t border-base-200 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-primary"></span>
                                        <span class="text-base-content/70 font-medium">{{ __('Dokumen Dibuat') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full bg-sky-400"></span>
                                        <span class="text-base-content/70 font-medium">{{ __('Dokumen Disetujui (Aktif)') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Target Gauge / Compliance Summary Card --}}
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-5 shadow-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <h3 class="font-bold text-base text-base-content">{{ __('Tingkat Kepatuhan & Efisiensi') }}</h3>
                                        <p class="text-xs text-base-content/50">
                                            {{ __('Rasio dokumen aktif terhadap seluruh draf dokumen organisasi.') }}
                                        </p>
                                    </div>
                                    <span class="badge badge-outline badge-sm text-base-content/60 font-mono">
                                        {{ __('Target Organisasi') }}: 100%
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center mt-3">
                                    <div class="md:col-span-5 flex justify-center">
                                        <div id="chart-compliance-gauge" class="w-full flex justify-center"></div>
                                    </div>
                                    <div class="md:col-span-7 space-y-3">
                                        <div class="bg-primary/5 border border-primary/15 rounded-xl p-3.5">
                                            <div class="text-xs font-bold text-primary flex items-center gap-1.5">
                                                <span>🎉</span>
                                                <span>{{ __('Pencapaian Sangat Baik!') }}</span>
                                            </div>
                                            <p class="text-[11.5px] text-base-content/70 mt-1 leading-relaxed">
                                                {{ __('Sebanyak') }} <strong class="text-base-content">{{ number_format($activeDocsCount) }}</strong> {{ __('dari') }} <strong class="text-base-content">{{ number_format($totalDocsCount) }}</strong> {{ __('dokumen telah terbit & disetujui secara tertib sesuai SOP.') }}
                                            </p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="bg-base-200/50 rounded-xl p-2.5 text-center border border-base-200">
                                                <div class="text-[10px] uppercase font-bold text-base-content/50">{{ __('Total Pengguna') }}</div>
                                                <div class="text-base font-black text-base-content mt-0.5">{{ number_format($totalUsersCount) }}</div>
                                            </div>
                                            <div class="bg-base-200/50 rounded-xl p-2.5 text-center border border-base-200">
                                                <div class="text-[10px] uppercase font-bold text-base-content/50">{{ __('Unit Kerja Aktif') }}</div>
                                                <div class="text-base font-black text-base-content mt-0.5">{{ number_format($totalUnitKerjaCount) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- Right Column (Top Categories Donut Chart + Breakdown - Span 4 Cols) --}}
                        <div class="lg:col-span-4 space-y-5">
                            
                            {{-- Donut Chart Card (Top Categories) --}}
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-5 shadow-xs space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-bold text-base text-base-content">{{ __('Jenis Dokumen Teratas') }}</h3>
                                        <p class="text-xs text-base-content/50">{{ __('Komposisi berdasarkan tipe arsip') }}</p>
                                    </div>
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.document-types.index') }}" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                                            <span>{{ __('Kelola Tipe') }}</span>
                                            <span>→</span>
                                        </a>
                                    @else
                                        <a href="{{ route('director.documents.index') }}" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                                            <span>{{ __('Jelajah Kategori') }}</span>
                                            <span>→</span>
                                        </a>
                                    @endif
                                </div>

                                {{-- Donut Chart Container --}}
                                <div class="w-full flex justify-center py-1">
                                    <div id="chart-document-donut" class="w-full flex justify-center"></div>
                                </div>

                                {{-- Category Breakdown List (Brand Primary Theme) --}}
                                <div class="space-y-2.5 pt-2 border-t border-base-200 text-xs">
                                    @php
                                        $dotColors = ['bg-primary', 'bg-sky-500', 'bg-cyan-500', 'bg-sky-300', 'bg-base-300'];
                                    @endphp
                                    @foreach($docTypeLabels as $idx => $label)
                                        @php
                                            $count = $docTypeSeries[$idx] ?? 0;
                                            $pct = $totalDocsCount > 0 ? round(($count / $totalDocsCount) * 100, 1) : 0;
                                        @endphp
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-2.5 h-2.5 rounded-full {{ $dotColors[$idx % count($dotColors)] }} shrink-0"></span>
                                                <span class="text-base-content/80 font-medium truncate" title="{{ $label }}">{{ $label }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0 font-mono">
                                                <span class="text-base-content/50 text-[11px]">{{ $pct }}%</span>
                                                <span class="font-bold text-base-content">{{ number_format($count) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Top Active Unit Kerja Progress Card (Brand Primary Theme) --}}
                            <div class="bg-base-100 border border-base-300 rounded-2xl p-5 shadow-xs space-y-3.5">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-base text-base-content">{{ __('Unit Kerja Teraktif') }}</h3>
                                    <span class="badge badge-ghost badge-xs font-mono font-semibold">{{ __('Top 4') }}</span>
                                </div>
                                <div class="space-y-3">
                                    @forelse($topUnitKerjas as $uk)
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-semibold text-base-content truncate max-w-[160px]" title="{{ $uk['name'] }}">
                                                    {{ $uk['name'] }}
                                                </span>
                                                <div class="flex items-center gap-1.5 font-mono text-[11px]">
                                                    <span class="text-base-content/60">{{ $uk['count'] }} dok</span>
                                                    <span class="font-bold text-primary">{{ $uk['percentage'] }}%</span>
                                                </div>
                                            </div>
                                            <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                                                <div class="bg-primary h-2 rounded-full" style="width: {{ max(6, $uk['percentage']) }}%"></div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-base-content/50 py-3 text-center">{{ __('Belum ada data unit kerja.') }}</p>
                                    @endforelse
                                </div>
                            </div>

                        </div>

                    </div>

                    {{-- Bottom Row: Recent Documents Table --}}
                    <div class="bg-base-100 border border-base-300 rounded-2xl shadow-xs overflow-hidden">
                        <div class="p-4 sm:p-5 border-b border-base-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                    <span>{{ __('Dokumen Terbaru') }}</span>
                                </h3>
                                <p class="text-xs text-base-content/50 mt-0.5">
                                    {{ __('Naskah dan berkas yang baru saja dibuat atau diperbarui.') }}
                                </p>
                            </div>
                            @if(auth()->user()->isDirector())
                                <a href="{{ route('director.documents.index') }}" class="btn btn-ghost btn-xs text-primary hover:bg-primary/10 font-bold gap-1.5 self-start sm:self-auto rounded-lg px-2.5 py-1.5 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>{{ __('Jelajah Semua Dokumen') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('admin.documents.index') }}" class="btn btn-ghost btn-xs text-primary hover:bg-primary/10 font-bold gap-1 self-start sm:self-auto rounded-lg px-2.5 py-1.5 transition-all">
                                    <span>{{ __('Lihat Semua Arsip') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table w-full text-xs">
                                <thead>
                                    <tr class="bg-base-200/50 text-base-content/70 border-b border-base-200">
                                        <th class="py-3">{{ __('Dokumen') }}</th>
                                        <th class="py-3 whitespace-nowrap">{{ __('Perusahaan & Cabang') }}</th>
                                        <th class="py-3 whitespace-nowrap">{{ __('Jenis Dokumen') }}</th>
                                        <th class="py-3 whitespace-nowrap">{{ __('Pembuat & Tanggal') }}</th>
                                        <th class="py-3 whitespace-nowrap">{{ __('Status') }}</th>
                                        <th class="text-right py-3">{{ __('Aksi') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-base-200/60">
                                    @forelse($recentDocuments as $doc)
                                        @php
                                            $displayVer = $doc->displayVersion();
                                            $hasDraft = $doc->hasDraft();
                                            $hasPending = $doc->hasPending();
                                            $hasRejected = $doc->hasRejected();
                                            $isActive = $displayVer && $displayVer->status === 'active' && !$doc->is_expired;
                                            $branch = $doc->branch;
                                            $isPusat = $branch && ($branch->is_pusat || strcasecmp(trim($branch->name), 'pusat') === 0);
                                        @endphp
                                        <tr class="hover:bg-base-200/40 transition-colors">
                                            {{-- Title & Number --}}
                                            <td class="py-3">
                                                <div class="space-y-0.5 min-w-[200px]">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <a href="{{ route('documents.show', $doc) }}" class="font-bold text-xs text-base-content hover:text-primary transition-colors line-clamp-1 break-words" title="{{ $doc->title }}">
                                                            {{ $doc->title }}
                                                        </a>
                                                        @if(auth()->user()->isDirector() && $doc->director_notified_at && !$doc->director_read_at)
                                                            <span class="badge badge-warning badge-xs font-bold leading-none">{{ __('Tembusan Baru') }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-1.5 text-[11px] text-base-content/50">
                                                        @if($doc->document_number)
                                                            <span class="font-mono bg-base-200/80 px-1.5 py-0.5 rounded border border-base-300 font-medium">{{ $doc->document_number }}</span>
                                                        @endif
                                                        @if($displayVer)
                                                            <span class="badge badge-ghost badge-xs font-mono font-bold">v{{ $displayVer->version_number }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Company & Branch --}}
                                            <td class="py-3 whitespace-nowrap">
                                                <div class="inline-flex items-center gap-1.5 text-xs">
                                                    <span class="font-semibold text-base-content">{{ $doc->branch?->company?->name ?? $doc->company?->name ?? '—' }}</span>
                                                    @if($isPusat)
                                                        <span class="badge badge-primary badge-xs font-bold leading-none">{{ __('Pusat') }}</span>
                                                    @elseif($branch)
                                                        <span class="text-base-content/30">•</span>
                                                        <span class="text-base-content/70 text-[11px] font-medium">{{ $branch->name }}</span>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Document Type --}}
                                            <td class="py-3 whitespace-nowrap">
                                                @if($doc->documentType)
                                                    <div class="inline-flex items-center gap-1.5 text-xs">
                                                        @if($doc->documentType->code)
                                                            <span class="badge badge-primary/10 text-primary border-primary/20 font-mono font-bold badge-xs">{{ $doc->documentType->code }}</span>
                                                        @endif
                                                        <span class="font-medium text-base-content text-[11.5px]">{{ $doc->documentType->name }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-base-content/40 font-mono text-xs">—</span>
                                                @endif
                                            </td>

                                            {{-- Owner & Date --}}
                                            <td class="py-3 whitespace-nowrap">
                                                <div class="space-y-0.5">
                                                    <div class="font-semibold text-xs text-base-content truncate max-w-[130px]">{{ $doc->owner?->name ?? '—' }}</div>
                                                    <div class="text-[11px] text-base-content/50">{{ $doc->created_at ? $doc->created_at->format('d M Y, H:i') : '—' }}</div>
                                                </div>
                                            </td>

                                            {{-- Status --}}
                                            <td class="py-3 whitespace-nowrap">
                                                @if($doc->is_expired)
                                                    <span class="badge badge-error badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                        {{ __('Kedaluwarsa') }}
                                                    </span>
                                                @elseif($hasPending)
                                                    <span class="badge badge-warning badge-sm text-neutral font-medium gap-1.5 shadow-2xs leading-none">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-neutral/80"></span>
                                                        {{ __('Menunggu Review') }}
                                                    </span>
                                                @elseif($isActive)
                                                    <span class="badge badge-success badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                                        {{ __('Aktif') }}
                                                    </span>
                                                @elseif($hasRejected)
                                                    <span class="badge badge-error/15 text-error border border-error/30 badge-sm font-medium gap-1.5 leading-none">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                        {{ __('Ditolak') }}
                                                    </span>
                                                @elseif($hasDraft)
                                                    <span class="badge badge-ghost badge-sm font-medium border border-base-300 gap-1.5 text-base-content/80 leading-none">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/40"></span>
                                                        {{ __('Draf') }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-ghost badge-sm border border-base-300 font-medium leading-none">{{ ucfirst($displayVer->status ?? 'Draft') }}</span>
                                                @endif
                                            </td>

                                            {{-- Actions --}}
                                            <td class="py-3 text-right whitespace-nowrap">
                                                <a href="{{ route('documents.show', $doc) }}" class="btn btn-ghost btn-xs btn-square text-primary" title="{{ __('Buka Dokumen') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-8 text-base-content/50">
                                                {{ __('Belum ada dokumen yang diterbitkan.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                {{-- Admin & PIC Expiring Documents Recap Modal --}}
                <dialog id="admin-expiring-modal" class="modal" x-data="{ tab: '{{ $isPicUnitKerja && $picExpiringDocs->isNotEmpty() ? 'pic' : ($ownerExpiringDocs->isNotEmpty() ? 'owner' : 'shared') }}' }">
                    <div class="modal-box max-w-3xl">
                        <form method="dialog">
                            <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                        </form>
                        <div class="flex items-center gap-3 text-warning mb-4">
                            <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content">{{ __('Rekap Dokumen Masa Berlaku & Kadaluwarsa') }}</h3>
                                <p class="text-xs text-base-content/60">{{ __('Menampilkan dokumen berbatas waktu yang mendekati kadaluwarsa (≤30 hari) atau telah kadaluwarsa.') }}</p>
                            </div>
                        </div>

                        {{-- Modal Tabs --}}
                        <div class="flex items-center gap-2 border-b border-base-200 pb-2 mb-3">
                            @if($isPicUnitKerja)
                                <button type="button" @click="tab = 'pic'" :class="tab === 'pic' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                                    {{ __('Rekap Unit Kerja') }} ({{ $picExpiringDocs->count() }})
                                </button>
                            @endif
                            <button type="button" @click="tab = 'owner'" :class="tab === 'owner' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                                {{ __('Dokumen Saya') }} ({{ $ownerExpiringDocs->count() }})
                            </button>
                            <button type="button" @click="tab = 'shared'" :class="tab === 'shared' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                                {{ __('Dibagikan ke Saya') }} ({{ $sharedExpiringDocs->count() }})
                            </button>
                        </div>

                        {{-- Tab 1: PIC Unit Kerja Recap --}}
                        @if($isPicUnitKerja)
                            <div x-show="tab === 'pic'" class="divide-y divide-base-200 max-h-96 overflow-y-auto">
                                @forelse($picExpiringDocs as $doc)
                                    @php $days = $doc->daysUntilExpiration(); @endphp
                                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                        <div class="space-y-0.5 min-w-0 flex-1">
                                            <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                                {{ $doc->title }}
                                            </a>
                                            <div class="text-[11px] text-base-content/50">
                                                <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span> • <span class="font-medium text-base-content/70">{{ $doc->owner?->name ?? '-' }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                                            @if($doc->isExpired())
                                                <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                                            @elseif($days <= 1)
                                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                                            @else
                                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen unit kerja yang mendekati masa kadaluwarsa.') }}</p>
                                @endforelse
                            </div>
                        @endif

                        {{-- Tab 2: Owner Recap --}}
                        <div x-show="tab === 'owner'" class="divide-y divide-base-200 max-h-96 overflow-y-auto">
                            @forelse($ownerExpiringDocs as $doc)
                                @php $days = $doc->daysUntilExpiration(); @endphp
                                <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                    <div class="space-y-0.5 min-w-0 flex-1">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                            {{ $doc->title }}
                                        </a>
                                        <div class="text-[11px] text-base-content/50">
                                            <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                                        @if($doc->isExpired())
                                            <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                                        @elseif($days <= 1)
                                            <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                                        @else
                                            <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen milik Anda yang mendekati masa kadaluwarsa.') }}</p>
                            @endforelse
                        </div>

                        {{-- Tab 3: Shared Recap --}}
                        <div x-show="tab === 'shared'" class="divide-y divide-base-200 max-h-96 overflow-y-auto">
                            @forelse($sharedExpiringDocs as $doc)
                                @php $days = $doc->daysUntilExpiration(); @endphp
                                <div class="py-3 flex items-center justify-between gap-3 text-xs">
                                    <div class="space-y-0.5 min-w-0 flex-1">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                            {{ $doc->title }}
                                        </a>
                                        <div class="text-[11px] text-base-content/50">
                                            <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span> • <span>Pemilik: {{ $doc->owner?->name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                                        @if($doc->isExpired())
                                            <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                                        @elseif($days <= 1)
                                            <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                                        @else
                                            <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen dibagikan yang mendekati masa kadaluwarsa.') }}</p>
                            @endforelse
                        </div>

                        <div class="modal-action mt-4">
                            <form method="dialog">
                                <button class="btn btn-sm btn-ghost">{{ __('Tutup') }}</button>
                            </form>
                        </div>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>

                {{-- Load ApexCharts Library & Initialize Charts with Primary Color Scheme --}}
                <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // 1. Spline Area Chart: Activity Analytics (Primary Brand Colors)
                        const analyticsOptions = {
                            series: [
                                {
                                    name: '{{ __("Dokumen Dibuat") }}',
                                    data: @json($chartCreated)
                                },
                                {
                                    name: '{{ __("Dokumen Disetujui") }}',
                                    data: @json($chartActive)
                                }
                            ],
                            chart: {
                                height: 280,
                                type: 'area',
                                toolbar: { show: false },
                                zoom: { enabled: false },
                                fontFamily: 'inherit',
                            },
                            colors: ['#0F6DB7', '#38bdf8'],
                            dataLabels: { enabled: false },
                            stroke: {
                                curve: 'smooth',
                                width: [3, 2.5],
                                dashArray: [0, 4]
                            },
                            fill: {
                                type: 'solid',
                                opacity: [0.15, 0.05]
                            },
                            xaxis: {
                                categories: @json($chartDates),
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: {
                                    style: {
                                        colors: '#9ca3af',
                                        fontSize: '11px',
                                        fontWeight: 500
                                    }
                                }
                            },
                            yaxis: {
                                min: 0,
                                forceNiceScale: true,
                                labels: {
                                    style: {
                                        colors: '#9ca3af',
                                        fontSize: '11px'
                                    }
                                }
                            },
                            grid: {
                                borderColor: 'rgba(156, 163, 175, 0.15)',
                                strokeDashArray: 4,
                                yaxis: { lines: { show: true } }
                            },
                            tooltip: {
                                theme: 'dark',
                                x: { show: true }
                            },
                            legend: { show: false }
                        };

                        const analyticsChart = new ApexCharts(document.querySelector("#chart-document-analytics"), analyticsOptions);
                        analyticsChart.render();

                        // 2. Semi-Circle Gauge Chart: Compliance Rate (Primary Brand Colors)
                        const complianceOptions = {
                            series: [{{ $complianceRate }}],
                            chart: {
                                type: 'radialBar',
                                height: 230,
                                offsetY: -10,
                                sparkline: { enabled: true }
                            },
                            plotOptions: {
                                radialBar: {
                                    startAngle: -90,
                                    endAngle: 90,
                                    track: {
                                        background: "rgba(156, 163, 175, 0.15)",
                                        strokeWidth: '97%',
                                        margin: 5
                                    },
                                    dataLabels: {
                                        name: { show: false },
                                        value: {
                                            offsetY: -15,
                                            fontSize: '28px',
                                            fontWeight: '800',
                                            color: '#0F6DB7',
                                            formatter: function (val) {
                                                return val + "%";
                                            }
                                        }
                                    }
                                }
                            },
                            fill: {
                                type: 'solid'
                            },
                            colors: ['#0F6DB7'],
                            labels: ['{{ __("Tingkat Kepatuhan") }}'],
                        };

                        const complianceChart = new ApexCharts(document.querySelector("#chart-compliance-gauge"), complianceOptions);
                        complianceChart.render();

                        // 3. Donut Chart: Document Types Distribution (Primary Brand Palette)
                        const donutOptions = {
                            series: @json($docTypeSeries),
                            chart: {
                                type: 'donut',
                                height: 220,
                                fontFamily: 'inherit'
                            },
                            labels: @json($docTypeLabels),
                            colors: ['#0F6DB7', '#0284c7', '#38bdf8', '#7dd3fc', '#cbd5e1'],
                            dataLabels: { enabled: false },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '72%',
                                        labels: {
                                            show: true,
                                            name: {
                                                fontSize: '11px',
                                                fontWeight: 600,
                                                color: '#6b7280'
                                            },
                                            value: {
                                                fontSize: '22px',
                                                fontWeight: 800,
                                                formatter: function (val) {
                                                    return val;
                                                }
                                            },
                                            total: {
                                                show: true,
                                                label: '{{ __("Total") }}',
                                                color: '#6b7280',
                                                formatter: function () {
                                                    return '{{ number_format($totalDocsCount) }}';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            legend: { show: false },
                            stroke: { width: 0 }
                        };

                        const donutChart = new ApexCharts(document.querySelector("#chart-document-donut"), donutOptions);
                        donutChart.render();
                    });
                </script>
            @else
                {{-- Non-admin dashboard: stats + recent documents --}}
                <div class="max-w-7xl mx-auto w-full space-y-6">
                    <!-- Search -->
                    <form method="GET" action="{{ route('dashboard') }}" class="card bg-base-100 border border-base-300 rounded-box p-4">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari judul atau nomor...') }}"
                                   class="input input-bordered input-sm w-full sm:flex-1">
                            <x-document-type-filter :documentTypes="$documentTypes" :selected="request('document_type_id')" placeholder="{{ __('Semua tipe') }}" />
                            <button type="submit" class="btn btn-outline btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                {{ __('Cari') }}
                            </button>
                            @if(request('search') || request('document_type_id'))
                                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Bersihkan') }}
                                </a>
                            @endif
                        </div>
                    </form>

                    @if($results)
                        <!-- Search Results -->
                        <div class="bg-base-100 border border-base-300 rounded-box">
                            <div class="px-4 sm:px-5 py-4 border-b border-base-300 flex flex-wrap items-center justify-between gap-2">
                                <h2 class="font-semibold text-base-content">{{ __('Hasil Pencarian') }}</h2>
                                <span class="text-sm text-base-content/50">{{ $results->total() }} {{ __('ditemukan') }}</span>
                            </div>
                            <div class="divide-y divide-base-200">
                                @forelse($results as $doc)
                                    <div class="px-4 sm:px-5 py-3.5 flex flex-wrap items-center justify-between gap-2 hover:bg-base-50 transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <svg class="w-8 h-8 shrink-0 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <div class="min-w-0 flex-1">
                                                <a href="{{ route('documents.show', $doc) }}" class="text-sm font-medium text-base-content hover:text-primary break-words block">{{ $doc->title }}</a>
                                                 <p class="text-xs text-base-content/60 mt-0.5 inline-flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $doc->document_number }}</span>
                                                    <span>·</span>
                                                    <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span>
                                                    <span>·</span>
                                                    <span class="inline-flex items-center gap-1 font-medium text-base-content/80">
                                                        <x-user-avatar :user="$doc->owner" size="w-3.5 h-3.5" text-size="text-[8px]" />
                                                        <span>{{ $doc->owner->name }}</span>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                                            <a href="{{ route('documents.preview', $doc) }}" title="{{ __('Pratinjau') }}" class="inline-flex items-center justify-center w-6 h-6 rounded-full text-base-content/60 hover:text-base-content hover:bg-base-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <div class="text-xs text-base-content/40 shrink-0">
                                                @if($doc->isGeneral()) <span class="text-success">{{ __('Umum') }}</span>
                                                @elseif($doc->isPersonal()) <span class="text-info">{{ __('Personal') }}</span>
                                                @else {{ $doc->unitKerja?->nama_unit_kerja }} @endif
                                                · {{ $doc->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 sm:px-5 py-10 text-center text-sm text-base-content/50">{{ __('Tidak ada dokumen yang cocok') }} "{{ request('search') }}".</div>
                                @endforelse
                            </div>
                            @if($results->hasPages())
                                <div class="p-4 border-t border-base-300">{{ $results->links() }}</div>
                            @endif
                        </div>
                    @endif

                    <!-- Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                            <div class="stat-title text-base-content/50 text-xs font-medium">{{ __('Total Dokumen') }}</div>
                            <div class="stat-value text-2xl font-bold text-primary mt-1">{{ $totalDocsCount ?? 0 }}</div>
                            <div class="stat-desc text-xs text-base-content/40 mt-1">{{ __('Sepanjang waktu') }}</div>
                        </div>
                        <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                            <div class="stat-title text-base-content/50 text-xs font-medium">{{ __('Dokumen Aktif') }}</div>
                            <div class="stat-value text-2xl font-bold text-primary mt-1">
                                {{ $activeDocsCount ?? 0 }}
                            </div>
                            <div class="stat-desc text-xs text-base-content/40 mt-1">{{ __('Disetujui & diterbitkan') }}</div>
                        </div>
                        <div class="stat bg-base-100 border border-base-300 rounded-box p-4">
                            <div class="stat-title text-base-content/50 text-xs font-medium">{{ __('Menunggu Persetujuan') }}</div>
                            <div class="stat-value text-2xl font-bold text-primary mt-1">
                                {{ $pendingDocsCount ?? 0 }}
                            </div>
                            <div class="stat-desc text-xs text-base-content/40 mt-1">{{ __('Menunggu review kepala') }}</div>
                        </div>
                        
                        <!-- Expiring Documents Stat -->
                        @php
                            $totalExpiringCount = $ownerExpiringDocs->count() + $sharedExpiringDocs->count() + ($isPicUnitKerja ? $picExpiringDocs->count() : 0);
                        @endphp
                        <div class="stat bg-base-100 border border-base-300 hover:border-warning/50 rounded-box p-4 cursor-pointer hover:bg-base-200 transition-colors" onclick="document.getElementById('expiring-modal').showModal()" title="{{ __('Lihat Daftar Dokumen') }}">
                            <div class="stat-title text-base-content/50 text-xs font-medium flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Masa Berlaku Dokumen') }}
                            </div>
                            <div class="stat-value text-2xl font-bold text-warning mt-1 flex justify-between items-end">
                                <span>{{ $totalExpiringCount }}</span>
                            </div>
                            <div class="stat-desc text-xs text-base-content/40 mt-1 flex items-center gap-1">
                                <span>{{ __('Dokumen berbatas waktu') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                        </div>
                    </div>


                    <!-- Recent Documents -->
                    <div class="bg-base-100 border border-base-300 rounded-box">
                        <div class="px-4 sm:px-5 py-4 border-b border-base-300 flex flex-wrap items-center justify-between gap-2">
                            <h2 class="font-semibold text-base-content">{{ __('Dokumen Terbaru') }}</h2>
                            <a href="{{ route('documents.index', ['type' => 'mine']) }}" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">{{ __('Lihat semua') }}</a>
                        </div>
                        <div class="divide-y divide-base-200">
                            @forelse($recent as $doc)
                                <div class="px-4 sm:px-5 py-3.5 flex flex-wrap items-center justify-between gap-2 hover:bg-base-50 transition-colors">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <svg class="w-8 h-8 shrink-0 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('documents.show', $doc) }}" class="text-sm font-medium text-base-content hover:text-primary break-words block">{{ $doc->title }}</a>
                                            <p class="text-xs text-base-content/40 mt-0.5">
                                                {{ $doc->document_number }} · {{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                                        <a href="{{ route('documents.preview', $doc) }}" title="{{ __('Pratinjau') }}" class="inline-flex items-center justify-center w-6 h-6 rounded-full text-base-content/60 hover:text-base-content hover:bg-base-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @php
                                            $displayVer = $doc->displayVersion();
                                            $hasDraft = $doc->hasDraft();
                                            $hasPending = $doc->hasPending();
                                            $hasRejected = $doc->hasRejected();
                                            $isActive = $displayVer && $displayVer->status === 'active' && !$doc->is_expired;
                                        @endphp
                                        @if($doc->isExpired() || $doc->is_expired)
                                            <span class="badge badge-error badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                {{ __('Kedaluwarsa') }}
                                            </span>
                                        @elseif($hasPending && $doc->currentVersion)
                                            <span class="badge badge-warning badge-sm w-auto px-2 justify-center font-medium" title="{{ __('Versi aktif v:active, menunggu persetujuan v:pending', ['active' => $doc->currentVersion->version_number, 'pending' => $displayVer?->version_number]) }}">
                                                v{{ $doc->currentVersion->version_number }} (v{{ $displayVer?->version_number }} {{ __('Pending') }})
                                            </span>
                                        @elseif($isActive || $doc->currentVersion)
                                            <span class="badge badge-success badge-sm gap-1 text-white font-semibold">
                                                <span class="w-1 h-1 rounded-full bg-white"></span>
                                                {{ __('Aktif') }}
                                            </span>
                                        @elseif($hasPending)
                                            <span class="badge badge-warning badge-sm px-2 justify-center">{{ __('Tertunda') }}</span>
                                        @elseif($hasRejected)
                                            <span class="badge badge-error/15 text-error border border-error/30 badge-sm font-medium gap-1.5 leading-none">
                                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                {{ __('Ditolak') }}
                                            </span>
                                        @elseif($hasDraft)
                                            <span class="badge badge-warning badge-sm px-2 justify-center">{{ __('Draf') }}</span>
                                        @else
                                            <span class="badge badge-ghost badge-sm px-2 justify-center">{{ ucfirst($displayVer->status ?? __('Tanpa versi')) }}</span>
                                        @endif
                                        <span class="text-xs text-base-content/30">{{ $doc->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 sm:px-5 py-10 text-center">
                                    <svg class="w-10 h-10 mx-auto text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <p class="text-sm text-base-content/50 mb-1">{{ __('Belum ada dokumen') }}</p>
                                    <p class="text-xs text-base-content/30">{{ __('Buat dokumen pertama Anda untuk memulai.') }}</p>
                                    <a href="{{ route('documents.choose') }}" class="btn btn-primary btn-sm mt-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        {{ __('Buat Dokumen') }}
                                    </a>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Expiring Documents Modal (User / Staff / PIC Recap) -->
    <dialog id="expiring-modal" class="modal" x-data="{ tab: '{{ $ownerExpiringDocs->isNotEmpty() ? 'owner' : ($sharedExpiringDocs->isNotEmpty() ? 'shared' : 'pic') }}' }">
        <div class="modal-box w-11/12 max-w-3xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg flex items-center gap-2 text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="text-base-content">{{ __('Rekap Dokumen Masa Berlaku') }}</span>
            </h3>
            <p class="py-2 text-xs text-base-content/70">
                {{ __('Daftar dokumen yang memiliki masa berlaku khusus yang mendekati kadaluwarsa (≤30 hari) atau telah kadaluwarsa.') }}
            </p>

            {{-- Modal Tabs --}}
            <div class="flex items-center gap-2 border-b border-base-200 pb-2 mb-3 mt-2">
                <button type="button" @click="tab = 'owner'" :class="tab === 'owner' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                    {{ __('Dokumen Saya') }} ({{ $ownerExpiringDocs->count() }})
                </button>
                <button type="button" @click="tab = 'shared'" :class="tab === 'shared' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                    {{ __('Dibagikan ke Saya') }} ({{ $sharedExpiringDocs->count() }})
                </button>
                @if($isPicUnitKerja)
                    <button type="button" @click="tab = 'pic'" :class="tab === 'pic' ? 'btn-primary text-white' : 'btn-ghost text-base-content/70'" class="btn btn-xs rounded-lg font-semibold">
                        {{ __('Rekap Unit Kerja') }} ({{ $picExpiringDocs->count() }})
                    </button>
                @endif
            </div>

            {{-- Tab 1: Owner --}}
            <div x-show="tab === 'owner'" class="divide-y divide-base-200 max-h-80 overflow-y-auto">
                @forelse($ownerExpiringDocs as $doc)
                    @php $days = $doc->daysUntilExpiration(); @endphp
                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                        <div class="space-y-0.5 min-w-0 flex-1">
                            <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                {{ $doc->title }}
                            </a>
                            <div class="text-[11px] text-base-content/50">
                                <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                            @if($doc->isExpired())
                                <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                            @elseif($days <= 1)
                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                            @else
                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen milik Anda yang mendekati masa kadaluwarsa.') }}</p>
                @endforelse
            </div>

            {{-- Tab 2: Shared --}}
            <div x-show="tab === 'shared'" class="divide-y divide-base-200 max-h-80 overflow-y-auto">
                @forelse($sharedExpiringDocs as $doc)
                    @php $days = $doc->daysUntilExpiration(); @endphp
                    <div class="py-3 flex items-center justify-between gap-3 text-xs">
                        <div class="space-y-0.5 min-w-0 flex-1">
                            <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                {{ $doc->title }}
                            </a>
                            <div class="text-[11px] text-base-content/50">
                                <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span> • <span>Pemilik: {{ $doc->owner?->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                            @if($doc->isExpired())
                                <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                            @elseif($days <= 1)
                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                            @else
                                <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen dibagikan yang mendekati masa kadaluwarsa.') }}</p>
                @endforelse
            </div>

            {{-- Tab 3: PIC Unit Kerja --}}
            @if($isPicUnitKerja)
                <div x-show="tab === 'pic'" class="divide-y divide-base-200 max-h-80 overflow-y-auto">
                    @forelse($picExpiringDocs as $doc)
                        @php $days = $doc->daysUntilExpiration(); @endphp
                        <div class="py-3 flex items-center justify-between gap-3 text-xs">
                            <div class="space-y-0.5 min-w-0 flex-1">
                                <a href="{{ route('documents.show', $doc) }}" class="font-bold text-base-content hover:text-primary transition-colors block truncate">
                                    {{ $doc->title }}
                                </a>
                                <div class="text-[11px] text-base-content/50">
                                    <span>{{ $doc->document_number ?? '—' }}</span> • <span>{{ $doc->unitKerja?->nama_unit_kerja ?? '—' }}</span> • <span class="font-medium text-base-content/70">{{ $doc->owner?->name ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-semibold text-xs text-base-content">{{ $doc->expiration_date->format('d M Y') }}</div>
                                @if($doc->isExpired())
                                    <span class="badge badge-error badge-xs text-white mt-0.5">{{ __('Kadaluwarsa') }}</span>
                                @elseif($days <= 1)
                                    <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Besok / Hari ini') }}</span>
                                @else
                                    <span class="badge badge-warning badge-xs text-white mt-0.5">{{ __('Sisa :days hari', ['days' => $days]) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-center py-6 text-base-content/50">{{ __('Tidak ada dokumen unit kerja yang mendekati masa kadaluwarsa.') }}</p>
                    @endforelse
                </div>
            @endif

            <div class="modal-action mt-4">
                <form method="dialog">
                    <button class="btn btn-sm btn-ghost">{{ __('Tutup') }}</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

</x-app-layout>