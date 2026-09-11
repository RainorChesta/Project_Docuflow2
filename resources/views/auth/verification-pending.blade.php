<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DokuFlow') }} — {{ __('Menunggu Verifikasi Administrator') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        {{-- Flash-prevention: set data-theme before CSS renders --}}
        <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light');document.documentElement.classList.toggle('dark',d);m.addEventListener('change',function(){var s=sessionStorage.getItem('theme:v2');var isDark=s==='dark'||(s!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',isDark?'dark':'light');document.documentElement.classList.toggle('dark',isDark)})})()</script>

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen max-h-[100dvh] w-full bg-gradient-to-br from-primary/10 via-base-200 to-secondary/10 font-sans antialiased flex flex-col justify-between p-3 sm:p-4 md:p-6 overflow-y-auto [@media(min-height:600px)_and_(min-width:768px)]:overflow-y-hidden"
          x-data="{
              hasDivision: {{ !empty($hasDivision) ? 'true' : 'false' }},
              divisionNames: {{ Js::from($divisionNames ?? []) }},
              hasCompany: {{ !empty($hasCompany) ? 'true' : 'false' }},
              companyNames: {{ Js::from($companyNames ?? []) }},
              hasBranch: {{ !empty($hasBranch) ? 'true' : 'false' }},
              branchNames: {{ Js::from($branchNames ?? []) }},
              isVerified: {{ !empty($isVerified) ? 'true' : 'false' }},
              isFetching: false,
              statusNotice: '',

              get hasCompanyAndBranch() {
                  return this.hasCompany && this.hasBranch;
              },

              get progressPercent() {
                  if (this.isVerified) return 100;
                  let completed = 1; // registration done
                  if (this.hasDivision) completed++;
                  if (this.hasCompanyAndBranch) completed++;
                  return Math.round((completed / 3) * 100);
              },

              checkStatus(manual = false) {
                  if (this.isFetching) return;
                  this.isFetching = true;

                  fetch('{{ route('verification.status') }}', {
                      headers: {
                          'Accept': 'application/json',
                          'X-Requested-With': 'XMLHttpRequest'
                      }
                  })
                  .then(res => res.json())
                  .then(data => {
                      const prevDiv = this.hasDivision;
                      const prevCompBranch = this.hasCompanyAndBranch;

                      this.hasDivision = data.has_division;
                      this.divisionNames = data.division_names || [];
                      this.hasCompany = data.has_company;
                      this.companyNames = data.company_names || [];
                      this.hasBranch = data.has_branch;
                      this.branchNames = data.branch_names || [];
                      this.isVerified = data.is_verified;

                      // Notifications when steps complete or on manual refresh
                      if (!prevDiv && data.has_division) {
                          this.statusNotice = '{{ __('Divisi Anda telah berhasil ditetapkan!') }}';
                      } else if (!prevCompBranch && (data.has_company && data.has_branch)) {
                          this.statusNotice = '{{ __('Perusahaan & Cabang Anda telah berhasil ditugaskan!') }}';
                      } else if (manual && !data.is_verified) {
                          this.statusNotice = '{{ __('Status verifikasi berhasil diperbarui.') }}';
                      }

                      if (data.is_verified) {
                          this.statusNotice = '{{ __('Akun Anda telah diverifikasi penuh! Mengalihkan ke Dashboard...') }}';
                          setTimeout(() => {
                              window.location.href = data.redirect_url || '{{ route('dashboard') }}';
                          }, 1200);
                      }
                  })
                  .catch(() => {
                      if (manual) {
                          this.statusNotice = '{{ __('Gagal memeriksa status. Silakan coba lagi.') }}';
                      }
                  })
                  .finally(() => {
                      this.isFetching = false;
                  });
              }
          }">
        <!-- Top Controls: Logo, Language & Theme -->
        <header class="w-full max-w-xl md:max-w-2xl mx-auto flex justify-between items-center shrink-0 mb-2 sm:mb-3">
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center gap-2 text-base-content hover:opacity-80 transition-opacity">
                    <x-application-logo class="h-6 w-6 sm:h-7 sm:w-7 text-primary" />
                    <span class="font-bold tracking-tight text-base sm:text-lg text-base-content">{{ config('app.name', 'DokuFlow') }}</span>
                </a>
            </div>
            <div class="flex items-center gap-2">
                <x-language-toggle />
                <x-theme-toggle />
            </div>
        </header>

        <!-- Main Locked Card (Responsive, Longer / Better Screen Utilization, Fits 100vh) -->
        <div class="w-full max-w-xl md:max-w-2xl mx-auto my-auto card bg-base-100 shadow-xl border border-base-200 dark:border-base-300 overflow-hidden shrink min-h-0">
            <!-- Top Brand Accent Ribbon -->
            <div class="h-1.5 w-full bg-gradient-to-r from-primary via-primary/80 to-accent shrink-0"></div>

            <div class="card-body p-4 sm:p-5 md:p-6 space-y-3 sm:space-y-3.5">
                <!-- 1. Header: Icon, Status Pill & Title -->
                <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                    <!-- Security Lock Icon with Pulse Aura -->
                    <div class="relative shrink-0 mt-0.5 sm:mt-0">
                        <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shadow-xs transition-colors duration-300"
                             :class="isVerified ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-primary/10 border border-primary/25 text-primary'">
                            <template x-if="isVerified">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </template>
                            <template x-if="!isVerified">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </template>
                        </div>
                        <span class="absolute -top-0.5 -right-0.5 flex h-3 w-3" x-show="!isVerified">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary/40 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-primary"></span>
                        </span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-1.5 mb-1">
                            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider transition-colors duration-300"
                                 :class="isVerified ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-primary/10 text-primary border border-primary/25'">
                                <template x-if="isVerified">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </template>
                                <template x-if="!isVerified">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <span x-text="isVerified ? '{{ __('Verifikasi Selesai') }}' : '{{ __('Akses Terkunci Sementara') }}'"></span>
                            </div>
                            <span class="badge badge-sm badge-ghost font-mono text-[10.5px] text-base-content/60 border-base-300 max-w-[200px] truncate">{{ $user->email }}</span>
                        </div>
                        <h1 class="text-base sm:text-lg md:text-xl font-extrabold text-base-content tracking-tight leading-tight">
                            <span x-text="isVerified ? '{{ __('Akun Telah Terverifikasi') }}' : '{{ __('Menunggu Verifikasi Admin') }}'"></span>
                        </h1>
                    </div>
                </div>

                <!-- 2. System Notification Box -->
                <div class="rounded-xl p-3 sm:p-3.5 border transition-colors duration-300 shadow-xs flex items-start gap-2.5 sm:gap-3"
                     :class="isVerified ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-300 dark:border-emerald-800/60 text-emerald-950 dark:text-emerald-100' : 'bg-primary/5 dark:bg-primary/10 border-primary/20 dark:border-primary/30 text-base-content'">
                    <div class="p-1.5 rounded-lg shrink-0 mt-0.5"
                         :class="isVerified ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-primary/15 text-primary'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="text-left flex-1 min-w-0">
                        <span class="font-bold block text-xs sm:text-sm tracking-tight mb-0.5"
                              :class="isVerified ? 'text-emerald-800 dark:text-emerald-200' : 'text-primary'">
                            {{ __('Pemberitahuan Sistem:') }}
                        </span>
                        <div class="text-[11.5px] sm:text-xs text-base-content/85 leading-relaxed">
                            <span x-show="!isVerified">
                                {!! __('Akun Anda sedang <strong class="text-base-content font-semibold">menunggu verifikasi oleh administrator sistem</strong>. Akses aplikasi dikunci sementara hingga penempatan divisi dan perusahaan/cabang disetujui.') !!}
                            </span>
                            <span x-show="isVerified">
                                {!! __('Akun Anda telah <strong class="text-base-content font-semibold">berhasil diverifikasi</strong> oleh administrator sistem.') !!}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Progress Bar & User Info -->
                <div class="bg-base-200/50 dark:bg-base-200/30 rounded-xl p-2.5 sm:p-3 border border-base-200 dark:border-base-300/60">
                    <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base-content/75 font-medium">{{ __('Kemajuan Verifikasi:') }}</span>
                            <span class="text-[11px] text-base-content/50 font-normal truncate hidden sm:inline">({{ $user->name }})</span>
                        </div>
                        <span class="text-primary font-bold text-xs sm:text-sm" x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full bg-base-300/70 dark:bg-base-300/40 rounded-full h-2 overflow-hidden">
                        <div class="h-full bg-primary transition-all duration-500 ease-out"
                             :style="`width: ${progressPercent}%;`"></div>
                    </div>
                </div>

                <!-- 4. Verification Steps Checklist (Vertical, Responsive & Spaced) -->
                <div class="space-y-1.5 sm:space-y-2">
                    <div class="text-[10px] sm:text-[10.5px] font-bold text-base-content/60 uppercase tracking-wider">
                        {{ __('Tahapan Verifikasi yang Diperlukan') }}
                    </div>

                    <!-- Step 1: Registration -->
                    <div class="flex items-center justify-between p-2 sm:p-2.5 px-3 rounded-xl bg-base-100 dark:bg-base-200/60 border border-base-200 dark:border-base-300/60 gap-2.5 sm:gap-3">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-base-content text-xs sm:text-[13px] leading-tight">{{ __('Pendaftaran Akun Mandiri') }}</div>
                                <div class="text-[11px] text-base-content/50 truncate">{{ $user->email }} &bull; {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25 whitespace-nowrap">{{ __('Selesai') }}</span>
                        </div>
                    </div>

                    <!-- Step 2: Division Assignment -->
                    <div class="flex items-center justify-between p-2 sm:p-2.5 px-3 rounded-xl border gap-2.5 sm:gap-3 transition-all duration-300"
                         :class="hasDivision ? 'bg-base-100 dark:bg-base-200/60 border-base-200 dark:border-base-300/60' : 'bg-base-100 dark:bg-base-200/60 border-primary/30 ring-1 ring-primary/10'">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                            <template x-if="hasDivision">
                                <div class="w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </template>
                            <template x-if="!hasDivision">
                                <div class="w-5 h-5 rounded-full bg-primary/15 text-primary flex items-center justify-center font-bold shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                                </div>
                            </template>

                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-base-content text-xs sm:text-[13px] leading-tight">
                                    {{ __('Penugasan Divisi Kerja') }}
                                </div>
                                <div class="text-[11px] truncate sm:whitespace-normal">
                                    <template x-if="hasDivision">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium truncate" x-text="divisionNames.length > 0 ? '{{ __('Ditugaskan ke:') }} ' + divisionNames.join(', ') : '{{ __('Divisi telah ditetapkan') }}'"></span>
                                    </template>
                                    <template x-if="!hasDivision">
                                        <span class="text-base-content/60 truncate">{{ __('Menunggu penetapan divisi oleh Admin.') }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <template x-if="hasDivision">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25 whitespace-nowrap">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Selesai') }}
                                </span>
                            </template>
                            <template x-if="!hasDivision">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-medium bg-primary/10 text-primary border border-primary/25 whitespace-nowrap">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                                    {{ __('Menunggu Admin') }}
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: Company & Branch Assignment -->
                    <div class="flex items-center justify-between p-2 sm:p-2.5 px-3 rounded-xl border gap-2.5 sm:gap-3 transition-all duration-300"
                         :class="hasCompanyAndBranch ? 'bg-base-100 dark:bg-base-200/60 border-base-200 dark:border-base-300/60' : ((hasDivision && !hasCompanyAndBranch) ? 'bg-base-100 dark:bg-base-200/60 border-primary/30 ring-1 ring-primary/10' : 'bg-base-100 dark:bg-base-200/60 border-base-200 dark:border-base-300/60')">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                            <template x-if="hasCompanyAndBranch">
                                <div class="w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </template>
                            <template x-if="!hasCompanyAndBranch">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center font-bold shrink-0"
                                     :class="(hasCompany || hasDivision) ? 'bg-primary/15 text-primary' : 'bg-base-200 text-base-content/40'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="(hasCompany || hasDivision) ? 'bg-primary animate-pulse' : 'bg-base-content/40'"></span>
                                </div>
                            </template>

                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-base-content text-xs sm:text-[13px] leading-tight">
                                    {{ __('Penugasan Perusahaan & Cabang') }}
                                </div>
                                <div class="text-[11px] truncate sm:whitespace-normal">
                                    <template x-if="hasCompanyAndBranch">
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium truncate" x-text="'{{ __('Perusahaan:') }} ' + (companyNames.join(', ') || '-') + ' • {{ __('Cabang:') }} ' + (branchNames.join(', ') || '-')"></span>
                                    </template>
                                    <template x-if="hasCompany && !hasBranch">
                                        <span class="text-primary font-medium truncate" x-text="'{{ __('Perusahaan:') }} ' + (companyNames.join(', ') || '-') + ' ({{ __('Menunggu cabang') }})'"></span>
                                    </template>
                                    <template x-if="!hasCompany">
                                        <span class="text-base-content/60 truncate">{{ __('Menunggu penugasan perusahaan & cabang.') }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <template x-if="hasCompanyAndBranch">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25 whitespace-nowrap">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Selesai') }}
                                </span>
                            </template>
                            <template x-if="hasCompany && !hasBranch">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-medium bg-primary/10 text-primary border border-primary/25 whitespace-nowrap">{{ __('Cabang Belum') }}</span>
                            </template>
                            <template x-if="!hasCompany">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-medium bg-base-200 text-base-content/60 border border-base-300 whitespace-nowrap">{{ __('Menunggu Admin') }}</span>
                            </template>
                        </div>
                    </div>

                    <!-- Step 4: Access -->
                    <div class="flex items-center justify-between p-2 sm:p-2.5 px-3 rounded-xl border gap-2.5 sm:gap-3 transition-all duration-300"
                         :class="isVerified ? 'bg-emerald-500/10 border-emerald-500/40' : 'bg-base-100 dark:bg-base-200/40 border-base-200 dark:border-base-300/40 opacity-75'">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center font-bold shrink-0"
                                 :class="isVerified ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-base-200 text-base-content/40'">
                                <template x-if="isVerified">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <template x-if="!isVerified">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-base-content text-xs sm:text-[13px] leading-tight" :class="isVerified ? 'text-emerald-600 dark:text-emerald-400' : ''">
                                    <span x-text="isVerified ? '{{ __('Akses Fitur Terbuka') }}' : '{{ __('Akses Fitur Aplikasi') }}'"></span>
                                </div>
                                <div class="text-[11px] text-base-content/50 truncate">
                                    <span x-text="isVerified ? '{{ __('Akun terverifikasi! Mengalihkan ke Dashboard...') }}' : '{{ __('Otomatis terbuka setelah verifikasi selesai.') }}'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-semibold whitespace-nowrap"
                                  :class="isVerified ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/25' : 'bg-base-200 text-base-content/60 border border-base-300'"
                                  x-text="isVerified ? '{{ __('Terbuka') }}' : '{{ __('Terkunci') }}'"></span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Status Toast / Banner -->
                <div x-show="statusNotice"
                     x-transition
                     class="border border-primary/25 dark:border-primary/35 text-[11px] sm:text-xs py-1.5 px-3 rounded-xl flex items-center gap-2 bg-primary/10 dark:bg-primary/20 text-base-content shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-semibold truncate" x-text="statusNotice"></span>
                </div>

                <!-- 5. Actions & Help Hint -->
                <div class="pt-2 sm:pt-2.5 border-t border-base-200 dark:border-base-300/60 space-y-2">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2.5">
                        <button type="button" 
                                @click="checkStatus(true)" 
                                :disabled="isFetching"
                                class="btn btn-primary btn-sm flex-1 gap-2 shadow-sm font-semibold text-xs sm:text-sm h-9 min-h-9 sm:h-9.5 sm:min-h-9.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300" :class="isFetching ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span x-text="isFetching ? '{{ __('Memeriksa...') }}' : '{{ __('Periksa Status') }}'"></span>
                        </button>

                        <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm w-full text-base-content/70 hover:text-error hover:bg-error/10 gap-1.5 text-xs sm:text-sm h-9 min-h-9 sm:h-9.5 sm:min-h-9.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ __('Keluar') }}
                            </button>
                        </form>
                    </div>

                    <p class="text-[10.5px] sm:text-[11px] text-base-content/50 text-center leading-relaxed">
                        {{ __('Hubungi Administrator Sistem atau SDM / IT unit kerja Anda untuk mempercepat verifikasi.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="w-full text-center text-[10.5px] sm:text-[11px] text-base-content/40 shrink-0 mt-2">
            &copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }} &bull; Enterprise Document Workflow System
        </footer>
    </body>
</html>
