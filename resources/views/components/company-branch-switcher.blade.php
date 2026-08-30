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
        submitSwitch() {
            $refs.switchForm.submit();
        },
        openModal() {
            $refs.contextModal.showModal();
        },
        closeModal() {
            $refs.contextModal.close();
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

        {{-- Mobile & Shrunk Screen: 3-point icon button (visible below md) --}}
        <div class="md:hidden">
            <button type="button"
                    class="btn btn-ghost btn-circle btn-sm hover:bg-base-200 transition-colors"
                    @click="openModal()"
                    title="{{ __('Pilih Perusahaan & Cabang') }}"
                    aria-label="{{ __('Pilih Perusahaan & Cabang') }}">
                {{-- 3-point vertical ellipsis icon --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                </svg>
            </button>

            {{-- Centered DaisyUI Modal Dialog (escapes header parent containment) --}}
            <dialog id="company-branch-modal" x-ref="contextModal" class="modal">
                <div class="modal-box w-11/12 max-w-md p-5 sm:p-6 space-y-4 text-left">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between pb-3 border-b border-base-200">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-base-content">{{ __('Ganti Perusahaan & Cabang') }}</h3>
                                <p class="text-xs text-base-content/50">{{ __('Pilih entitas kerja aktif Anda') }}</p>
                            </div>
                        </div>
                        <form method="dialog">
                            <button class="btn btn-ghost btn-circle btn-sm text-base-content/60 hover:text-base-content" aria-label="{{ __('Tutup') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </form>
                    </div>

                    {{-- Active Context Pill --}}
                    <div class="bg-base-200/60 rounded-xl p-3 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base-content/50 uppercase tracking-wider font-semibold text-[10px]">{{ __('Aktif:') }}</span>
                            <span class="badge badge-primary badge-sm font-semibold">{{ $activeCompany?->code ?? '-' }}</span>
                            <span class="font-medium text-base-content truncate">{{ $activeBranch?->name ?? '-' }}</span>
                        </div>
                    </div>

                    {{-- Form --}}
                    <form method="POST" action="{{ route('context.switch') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                <span>{{ __('Perusahaan') }}</span>
                                <span class="text-[10px] text-base-content/40 font-normal">{{ count($companies) }} {{ __('tersedia') }}</span>
                            </label>
                            <select name="company_id" x-model="companyId"
                                    class="select select-bordered select-sm w-full bg-base-100 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                                @foreach($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->code }} - {{ $comp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($branches->isNotEmpty())
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-base-content/80 flex items-center justify-between">
                                    <span>{{ __('Cabang') }}</span>
                                    <span class="text-[10px] text-base-content/40 font-normal">{{ count($branches) }} {{ __('tersedia') }}</span>
                                </label>
                                <select name="branch_id" x-model="branchId"
                                        class="select select-bordered select-sm w-full bg-base-100 text-sm focus:border-primary focus:ring-1 focus:ring-primary">
                                    @foreach($branches as $br)
                                        <option value="{{ $br->id }}">{{ $br->name }} @if($br->is_pusat)(Pusat)@else({{ $br->code }})@endif</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-base-200">
                            <button type="button" class="btn btn-ghost btn-sm" @click="closeModal()">
                                {{ __('Batal') }}
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Terapkan') }}
                            </button>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop bg-black/60 backdrop-blur-sm">
                    <button>close</button>
                </form>
            </dialog>
        </div>
    </div>
@endif
