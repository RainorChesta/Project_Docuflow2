@php
    $contextService = app(\App\Services\CompanyContextService::class);
    $user = auth()->user();
    $companies = $contextService->getAvailableCompanies($user);
    $activeCompanyId = (string) $contextService->getActiveCompanyId($user);
    $activeBranchId = (string) $contextService->getActiveBranchId($user);
    $activeDivisionId = (string) $contextService->getActiveDivisionId($user);
    
    $companiesData = $companies->map(function ($c) use ($contextService, $user) {
        $cBranches = $contextService->getAvailableBranches($user, $c->id);
        return [
            'id' => (string) $c->id,
            'name' => $c->name,
            'code' => $c->code,
            'branches' => $cBranches->map(function ($b) {
                return [
                    'id' => (string) $b->id,
                    'name' => $b->name . ($b->is_pusat ? ' (' . __('Pusat') . ')' : ($b->code ? ' (' . $b->code . ')' : '')),
                    'raw_name' => $b->name,
                    'is_pusat' => (bool) $b->is_pusat,
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $activeCompany = $companies->firstWhere('id', (int) $activeCompanyId);
    $activeBranches = $contextService->getAvailableBranches($user, (int) $activeCompanyId);
    $activeBranch = $activeBranches->firstWhere('id', (int) $activeBranchId);
    
    $globalDivisions = $contextService->getAvailableDivisions($user);
    $activeDivision = $globalDivisions->firstWhere('id', (int) $activeDivisionId);
@endphp

@if(!$user?->isDirector() && $companies->isNotEmpty())
    <div x-data="{
        activeCompanyId: '{{ $activeCompanyId }}',
        activeBranchId: '{{ $activeBranchId }}',
        activeDivisionId: '{{ $activeDivisionId }}',
        selectedCompanyId: '{{ $activeCompanyId }}',
        selectedBranchId: '{{ $activeBranchId }}',
        selectedDivisionId: '{{ $activeDivisionId }}',
        pendingCompanyId: '{{ $activeCompanyId }}',
        pendingBranchId: '{{ $activeBranchId }}',
        pendingDivisionId: '{{ $activeDivisionId }}',
        companies: {{ Js::from($companiesData) }},
        divisions: {{ Js::from($globalDivisions->map(fn($d) => ['id' => (string) $d->id, 'name' => $d->name, 'code' => $d->code])->values()->all()) }},
        isSwitching: false,

        get currentCompany() {
            return this.companies.find(c => c.id === this.activeCompanyId) || null;
        },
        get currentBranch() {
            if (!this.currentCompany) return null;
            return this.currentCompany.branches.find(b => b.id === this.activeBranchId) || null;
        },
        get targetCompany() {
            return this.companies.find(c => c.id === this.pendingCompanyId) || null;
        },
        get targetBranch() {
            if (!this.targetCompany) return null;
            return this.targetCompany.branches.find(b => b.id === this.pendingBranchId) 
                || this.targetCompany.branches[0] 
                || null;
        },
        get availableSelectedBranches() {
            const comp = this.companies.find(c => c.id === this.selectedCompanyId);
            return comp ? comp.branches : [];
        },
        get availableSelectedDivisions() {
            return this.divisions;
        },
        get availablePendingDivisions() {
            return this.divisions;
        },
        get targetDivision() {
            return this.divisions.find(d => d.id === this.pendingDivisionId) 
                || this.divisions[0] 
                || null;
        },

        onDesktopCompanyChange(newCompanyId) {
            if (newCompanyId === this.activeCompanyId) return;
            this.pendingCompanyId = newCompanyId;
            const targetComp = this.companies.find(c => c.id === newCompanyId);
            if (targetComp && targetComp.branches.length > 0) {
                this.pendingBranchId = targetComp.branches[0].id;
            } else {
                this.pendingBranchId = '';
            }
            this.pendingDivisionId = this.divisions.length > 0 ? this.divisions[0].id : '';
            this.openConfirmModal();
        },

        onDesktopBranchChange(newBranchId) {
            if (newBranchId === this.activeBranchId) return;
            this.pendingCompanyId = this.activeCompanyId;
            this.pendingBranchId = newBranchId;
            this.pendingDivisionId = this.divisions.length > 0 ? this.divisions[0].id : '';
            this.openConfirmModal();
        },

        onDesktopDivisionChange(newDivisionId) {
            if (newDivisionId === this.activeDivisionId) return;
            this.pendingCompanyId = this.activeCompanyId;
            this.pendingBranchId = this.activeBranchId;
            this.pendingDivisionId = newDivisionId;
            this.openConfirmModal();
        },

        onMobileCompanyChange(newCompanyId) {
            this.selectedCompanyId = newCompanyId;
            const comp = this.companies.find(c => c.id === newCompanyId);
            if (comp && comp.branches.length > 0) {
                this.selectedBranchId = comp.branches[0].id;
            } else {
                this.selectedBranchId = '';
            }
            this.selectedDivisionId = this.divisions.length > 0 ? this.divisions[0].id : '';
        },

        onMobileBranchChange(newBranchId) {
            this.selectedBranchId = newBranchId;
        },

        applyMobileSelection() {
            if (this.selectedCompanyId === this.activeCompanyId && this.selectedBranchId === this.activeBranchId && this.selectedDivisionId === this.activeDivisionId) {
                this.closeMobileModal();
                return;
            }
            this.pendingCompanyId = this.selectedCompanyId;
            this.pendingBranchId = this.selectedBranchId;
            this.pendingDivisionId = this.selectedDivisionId;
            this.closeMobileModal();
            this.openConfirmModal();
        },

        openConfirmModal() {
            if (this.$refs.confirmModal) {
                this.$refs.confirmModal.showModal();
            }
        },

        init() {
            window.addEventListener('pageshow', () => {
                this.isSwitching = false;
            });
        },

        closeConfirmModal() {
            // Never revert state if switch submission is already in progress
            if (this.isSwitching) return;

            if (this.$refs.confirmModal && this.$refs.confirmModal.open) {
                this.$refs.confirmModal.close();
            }
            // Revert state to current active values
            this.selectedCompanyId = this.activeCompanyId;
            this.selectedBranchId = this.activeBranchId;
            this.selectedDivisionId = this.activeDivisionId;
            this.pendingCompanyId = this.activeCompanyId;
            this.pendingBranchId = this.activeBranchId;
            this.pendingDivisionId = this.activeDivisionId;
        },

        openMobileModal() {
            this.selectedCompanyId = this.activeCompanyId;
            this.selectedBranchId = this.activeBranchId;
            this.selectedDivisionId = this.activeDivisionId;
            if (this.$refs.mobileModal) {
                this.$refs.mobileModal.showModal();
            }
        },

        closeMobileModal() {
            if (this.$refs.mobileModal && this.$refs.mobileModal.open) {
                this.$refs.mobileModal.close();
            }
        },

        executeSwitch() {
            if (this.isSwitching) return;
            this.isSwitching = true;

            const finalCompany = this.pendingCompanyId;
            const finalBranch = this.targetBranch ? this.targetBranch.id : (this.pendingBranchId || '');
            const finalDivision = this.targetDivision ? this.targetDivision.id : (this.pendingDivisionId || '');

            const form = this.$refs.contextSwitchForm;
            if (form) {
                const compInput = form.querySelector('input[name=company_id]');
                const branchInput = form.querySelector('input[name=branch_id]');
                const divInput = form.querySelector('input[name=division_id]');
                if (compInput) compInput.value = finalCompany;
                if (branchInput) branchInput.value = finalBranch;
                if (divInput) divInput.value = finalDivision;

                // Safety timeout to prevent permanent stuck state on network failure
                setTimeout(() => {
                    this.isSwitching = false;
                }, 6000);

                form.submit();
            }
        }
    }">

        {{-- Hidden Central Form --}}
        <form x-ref="contextSwitchForm" method="POST" action="{{ route('context.switch') }}" class="hidden">
            @csrf
            <input type="hidden" name="company_id">
            <input type="hidden" name="branch_id">
            <input type="hidden" name="division_id">
        </form>

        {{-- Desktop: inline dropdowns (visible xl+) --}}
        <div class="hidden xl:flex items-center gap-1.5 2xl:gap-2 mr-1 shrink-0">
            {{-- Company Dropdown --}}
            <div class="relative">
                <select x-model="selectedCompanyId" 
                        @change="onDesktopCompanyChange($event.target.value)" 
                        class="select select-bordered select-xs sm:select-sm font-semibold bg-base-200/60 w-auto min-w-[130px] max-w-[200px] 2xl:max-w-[260px] focus:border-primary focus:ring-1 focus:ring-primary transition-colors cursor-pointer truncate"
                        title="{{ __('Pilih Perusahaan Aktif') }}">
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}">{{ $comp->code }} - {{ $comp->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Branch Dropdown --}}
            @if($activeBranches->isNotEmpty())
                <div class="relative">
                    <select x-model="selectedBranchId" 
                            @change="onDesktopBranchChange($event.target.value)" 
                            class="select select-bordered select-xs sm:select-sm text-xs bg-base-200/60 w-auto min-w-[110px] max-w-[160px] 2xl:max-w-[200px] focus:border-primary focus:ring-1 focus:ring-primary transition-colors cursor-pointer truncate"
                            title="{{ __('Pilih Cabang Aktif') }}">
                        @foreach($activeBranches as $br)
                            <option value="{{ $br->id }}">{{ $br->name }} @if($br->is_pusat)({{ __('Pusat') }})@else({{ $br->code }})@endif</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Division Dropdown --}}
            @if($globalDivisions->count() > 1)
                <div class="relative" x-show="availableSelectedDivisions.length > 1">
                    <select x-model="selectedDivisionId" 
                            @change="onDesktopDivisionChange($event.target.value)" 
                            class="select select-bordered select-xs sm:select-sm text-xs bg-base-200/60 w-auto min-w-[100px] max-w-[140px] 2xl:max-w-[180px] focus:border-primary focus:ring-1 focus:ring-primary transition-colors cursor-pointer truncate"
                            title="{{ __('Pilih Divisi Aktif') }}">
                        <template x-for="div in availableSelectedDivisions" :key="div.id">
                            <option :value="div.id" x-text="div.code + ' - ' + div.name"></option>
                        </template>
                    </select>
                </div>
            @endif
        </div>

        {{-- Tablet / Medium Screen: Compact pill button (visible sm to xl) --}}
        <div class="hidden sm:flex xl:hidden shrink-0">
            <button type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full bg-base-200/60 hover:bg-base-200 border border-base-300/60 hover:border-base-300 text-xs text-base-content/80 hover:text-base-content transition-all shadow-2xs group cursor-pointer max-w-[180px] md:max-w-[220px]"
                    @click="openMobileModal()"
                    title="{{ __('Ganti Perusahaan & Cabang') }}"
                    aria-label="{{ __('Pilih Perusahaan & Cabang') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="font-semibold truncate text-[11px]">{{ $activeCompany?->code ?? '-' }}</span>
                <span class="text-base-content/30">•</span>
                <span class="truncate text-[11px] text-base-content/70">{{ $activeBranch?->name ?? '-' }}</span>
                @if($globalDivisions->count() > 1)
                    <span class="text-base-content/30">•</span>
                    <span class="truncate text-[11px] text-base-content/70">{{ $activeDivision?->code ?? '-' }}</span>
                @endif
                <svg class="w-3 h-3 text-base-content/40 group-hover:text-base-content/70 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
            </button>
        </div>

        {{-- Mobile: Icon button (visible below sm) --}}
        <div class="sm:hidden shrink-0">
            <button type="button"
                    class="btn btn-ghost btn-circle btn-sm hover:bg-base-200 transition-colors"
                    @click="openMobileModal()"
                    title="{{ __('Pilih Perusahaan & Cabang') }}"
                    aria-label="{{ __('Pilih Perusahaan & Cabang') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </button>
        </div>

        {{-- Context Picker Modal (accessible on all screen sizes) --}}
        <dialog id="company-branch-modal" x-ref="mobileModal" class="modal modal-bottom sm:modal-middle" @close="closeMobileModal()">
            <div class="modal-box w-full sm:max-w-md p-0 overflow-hidden rounded-t-3xl sm:rounded-3xl bg-base-100 shadow-2xl border border-base-200/80 text-left">
                <div class="h-1.5 w-full bg-gradient-to-r from-primary via-indigo-500 to-primary/40"></div>
                
                <div class="p-5 sm:p-6 space-y-4">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-base-200/80">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 border border-primary/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content">{{ __('Ganti Perusahaan & Cabang') }}</h3>
                                <p class="text-xs text-base-content/50">{{ __('Pilih entitas kerja aktif Anda') }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-circle btn-sm text-base-content/60 hover:text-base-content" @click="closeMobileModal()" aria-label="{{ __('Tutup') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Active Context Pill --}}
                    <div class="bg-base-200/50 rounded-2xl p-3.5 flex items-center justify-between text-xs border border-base-300/40">
                        <div class="flex items-center gap-2">
                            <span class="text-base-content/50 uppercase tracking-wider font-bold text-[10px]">{{ __('Aktif:') }}</span>
                            <span class="badge badge-primary badge-sm font-bold">{{ $activeCompany?->code ?? '-' }}</span>
                            <span class="font-semibold text-base-content truncate">{{ $activeBranch?->name ?? '-' }}</span>
                        </div>
                    </div>

                    {{-- Form inputs --}}
                    <div class="space-y-3.5">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                <span>{{ __('Perusahaan') }}</span>
                                <span class="text-[10px] text-base-content/40 font-normal">{{ count($companies) }} {{ __('tersedia') }}</span>
                            </label>
                            <select x-model="selectedCompanyId" 
                                    @change="onMobileCompanyChange($event.target.value)"
                                    class="select select-bordered select-sm w-full bg-base-100 text-sm font-medium focus:border-primary focus:ring-1 focus:ring-primary rounded-xl">
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->code }} - {{ $comp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1.5" x-show="availableSelectedBranches.length > 0">
                            <label class="text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                <span>{{ __('Cabang') }}</span>
                                <span class="text-[10px] text-base-content/40 font-normal" x-text="availableSelectedBranches.length + ' {{ __('tersedia') }}'"></span>
                            </label>
                            <select x-model="selectedBranchId"
                                    @change="onMobileBranchChange($event.target.value)"
                                    class="select select-bordered select-sm w-full bg-base-100 text-sm font-medium focus:border-primary focus:ring-1 focus:ring-primary rounded-xl">
                                <template x-for="br in availableSelectedBranches" :key="br.id">
                                    <option :value="br.id" x-text="br.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="space-y-1.5" x-show="availableSelectedDivisions.length > 1">
                            <label class="text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                <span>{{ __('Divisi') }}</span>
                                <span class="text-[10px] text-base-content/40 font-normal" x-text="availableSelectedDivisions.length + ' {{ __('tersedia') }}'"></span>
                            </label>
                            <select x-model="selectedDivisionId"
                                    class="select select-bordered select-sm w-full bg-base-100 text-sm font-medium focus:border-primary focus:ring-1 focus:ring-primary rounded-xl">
                                <template x-for="div in availableSelectedDivisions" :key="div.id">
                                    <option :value="div.id" x-text="div.code + ' - ' + div.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-base-200/80">
                            <button type="button" class="btn btn-ghost btn-sm rounded-xl font-medium" @click="closeMobileModal()">
                                {{ __('Batal') }}
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-5 rounded-xl font-bold gap-1.5 shadow-md shadow-primary/20" @click="applyMobileSelection()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Terapkan') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop bg-black/60 backdrop-blur-sm">
                <button @click="closeMobileModal()">{{ __('Tutup') }}</button>
            </form>
        </dialog>

        {{-- Redesigned Confirmation Modal Dialog --}}
        <dialog id="context-confirm-dialog" x-ref="confirmModal" class="modal modal-bottom sm:modal-middle" @close="closeConfirmModal()">
            <div class="modal-box w-full sm:max-w-md p-0 overflow-hidden rounded-t-3xl sm:rounded-3xl bg-base-100 shadow-2xl border border-base-200/80 text-left">
                
                {{-- Top gradient hairline --}}
                <div class="h-1.5 w-full bg-gradient-to-r from-primary via-indigo-500 to-primary/40"></div>

                <div class="p-5 sm:p-7 space-y-5">
                    {{-- Header with icon --}}
                    <div class="flex items-start gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-primary/20 via-primary/10 to-transparent border border-primary/25 flex items-center justify-center shrink-0 text-primary shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="space-y-0.5 flex-1 min-w-0">
                            <h3 class="font-bold text-base sm:text-lg text-base-content tracking-tight">
                                {{ __('Konfirmasi Pengalihan Entitas') }}
                            </h3>
                            <p class="text-xs text-base-content/60 leading-relaxed">
                                {{ __('Apakah Anda yakin ingin beralih ke entitas perusahaan & cabang berikut?') }}
                            </p>
                        </div>
                    </div>

                    {{-- Visual Flow Cards (From -> To) --}}
                    <div class="space-y-2">
                        {{-- Current Entity Box --}}
                        <div class="p-3.5 rounded-2xl bg-base-200/50 border border-base-300/60 transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-base-content/45 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-base-content/30 inline-block"></span>
                                    {{ __('Entitas Saat Ini:') }}
                                </span>
                                <span class="badge badge-ghost badge-sm text-[11px] font-semibold" x-text="currentCompany?.code || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-xs sm:text-sm">
                                <div class="font-semibold text-base-content truncate flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-text="currentCompany?.name || '-'"></span>
                                </div>
                                <span class="badge badge-sm badge-neutral/10 text-base-content/70 font-medium shrink-0" x-text="currentBranch?.raw_name || currentBranch?.name || '-'"></span>
                            </div>
                        </div>

                        {{-- Connecting Arrow Badge --}}
                        <div class="flex items-center justify-center -my-1">
                            <div class="w-7 h-7 rounded-full bg-base-100 border border-base-300 shadow-sm flex items-center justify-center text-primary z-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>
                        </div>

                        {{-- Target Entity Box --}}
                        <div class="p-3.5 rounded-2xl bg-primary/[0.07] border-2 border-primary/40 shadow-sm transition-all">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider text-primary flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary inline-block animate-ping"></span>
                                    {{ __('Entitas Tujuan:') }}
                                </span>
                                <span class="badge badge-primary badge-sm text-[11px] font-bold" x-text="targetCompany?.code || '-'"></span>
                            </div>
                            <div class="flex items-center justify-between gap-2 text-xs sm:text-sm">
                                <div class="font-bold text-primary truncate flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span x-text="targetCompany?.name || '-'"></span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="badge badge-sm badge-primary font-semibold" x-text="targetBranch?.raw_name || targetBranch?.name || '-'"></span>
                                    <template x-if="availablePendingDivisions.length > 1">
                                        <span class="badge badge-sm badge-outline text-primary font-semibold" x-text="targetDivision?.code || targetDivision?.name || '-'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Context information footnote --}}
                    <div class="flex items-start gap-2.5 text-[11px] text-base-content/65 bg-base-200/40 rounded-xl p-3 border border-base-300/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="leading-relaxed">
                            {{ __('Halaman akan dimuat ulang untuk memperbarui daftar dokumen, alur persetujuan, dan hak akses sesuai entitas yang dipilih.') }}
                        </span>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2.5 pt-1">
                        <button type="button" 
                                class="btn btn-ghost flex-1 h-10 sm:h-11 min-h-0 rounded-xl font-medium border border-base-300/70 hover:bg-base-200"
                                :disabled="isSwitching"
                                @click="closeConfirmModal()">
                            {{ __('Batal') }}
                        </button>

                        <button type="button" 
                                class="btn btn-primary flex-1 h-10 sm:h-11 min-h-0 rounded-xl font-bold shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 transition-all gap-2"
                                :disabled="isSwitching"
                                @click="executeSwitch()">
                            <template x-if="isSwitching">
                                <span class="loading loading-spinner loading-xs"></span>
                            </template>
                            <template x-if="!isSwitching">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <span x-text="isSwitching ? '{{ __('Memproses...') }}' : '{{ __('Ya, Beralih') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <form method="dialog" class="modal-backdrop bg-black/60 backdrop-blur-sm">
                <button @click="closeConfirmModal()">{{ __('Tutup') }}</button>
            </form>
        </dialog>
    </div>
@endif


