@php
    $contextService = app(\App\Services\CompanyContextService::class);
    $user = auth()->user();
    $companies = $contextService->getAvailableCompanies($user);
    $activeCompanyId = $contextService->getActiveCompanyId($user);
    $branches = $contextService->getAvailableBranches($user, $activeCompanyId);
    $activeBranchId = $contextService->getActiveBranchId($user);
    $activeCompany = $companies->firstWhere('id', $activeCompanyId);
    $activeBranch = $branches->firstWhere('id', $activeBranchId);
@endphp

@if(!$user?->isDirector() && $companies->isNotEmpty())
    <div x-data="{
        companyId: '{{ $activeCompanyId }}',
        branchId: '{{ $activeBranchId }}',
        switcherOpen: false,
        submitSwitch() {
            $refs.switchForm.submit();
        },
        submitSwitcherMobile() {
            $refs.switchFormMobile.submit();
        }
    }">
        {{-- Desktop: inline dropdowns (visible md+) --}}
        <div class="hidden md:flex items-center gap-1.5 sm:gap-2 mr-2">
            <form x-ref="switchForm" method="POST" action="{{ route('context.switch') }}" class="flex items-center gap-1.5 sm:gap-2">
                @csrf
                {{-- Company Dropdown --}}
                <div class="relative">
                    <select name="company_id" x-model="companyId" @change="submitSwitch()" 
                            class="select select-bordered select-xs sm:select-sm font-semibold bg-base-200/60 w-auto min-w-[150px] sm:min-w-[200px] max-w-[260px] sm:max-w-[340px]"
                            title="{{ __('Pilih Perusahaan Aktif') }}">
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->code }} - {{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Branch Dropdown --}}
                @if($branches->isNotEmpty())
                    <div class="relative">
                        <select name="branch_id" x-model="branchId" @change="submitSwitch()" 
                                class="select select-bordered select-xs sm:select-sm text-xs bg-base-200/60 w-auto min-w-[130px] sm:min-w-[170px] max-w-[220px] sm:max-w-[280px]"
                                title="{{ __('Pilih Cabang Aktif') }}">
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }} @if($br->is_pusat)(Pusat)@else({{ $br->code }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </form>
        </div>

        {{-- Mobile: ellipsis icon + dropdown panel (visible below md) --}}
        <div class="relative md:hidden mr-1">
            <button type="button"
                    class="btn btn-ghost btn-sm btn-square"
                    @click="switcherOpen = !switcherOpen"
                    @click.away="switcherOpen = false"
                    title="{{ __('Perusahaan & Cabang') }}"
                    aria-label="{{ __('Perusahaan & Cabang') }}">
                {{-- Ellipsis vertical icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </button>

            {{-- Dropdown panel --}}
            <div x-show="switcherOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                 class="absolute right-0 top-full mt-2 w-72 bg-base-100 border border-base-300 rounded-xl shadow-lg p-4 space-y-3 z-50"
                 @click.away="switcherOpen = false"
                 x-cloak>

                <form x-ref="switchFormMobile" method="POST" action="{{ route('context.switch') }}" class="space-y-3">
                    @csrf
                    {{-- Active context summary --}}
                    <div class="flex items-center gap-2 pb-2 border-b border-base-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span class="text-xs font-bold text-base-content uppercase tracking-wider">{{ __('Konteks Aktif') }}</span>
                    </div>

                    {{-- Company --}}
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-base-content/60">{{ __('Perusahaan') }}</label>
                        <select name="company_id" x-model="companyId" @change="submitSwitcherMobile()"
                                class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-sm focus:border-primary transition-all">
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}">{{ $comp->code }} - {{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Branch --}}
                    @if($branches->isNotEmpty())
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-base-content/60">{{ __('Cabang') }}</label>
                            <select name="branch_id" x-model="branchId" @change="submitSwitcherMobile()"
                                    class="select select-bordered select-sm w-full text-xs bg-base-100 shadow-sm focus:border-primary transition-all">
                                @foreach($branches as $br)
                                    <option value="{{ $br->id }}">{{ $br->name }} @if($br->is_pusat)(Pusat)@else({{ $br->code }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Current context display --}}
                    <div class="pt-2 border-t border-base-200">
                        <div class="flex items-center gap-1.5 text-xs text-base-content/50">
                            <span class="badge badge-xs badge-primary">{{ $activeCompany?->code ?? '-' }}</span>
                            <span>·</span>
                            <span class="truncate">{{ $activeBranch?->name ?? '-' }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
