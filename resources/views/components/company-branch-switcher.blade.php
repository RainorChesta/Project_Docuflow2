@php
    $contextService = app(\App\Services\CompanyContextService::class);
    $user = auth()->user();
    $companies = $contextService->getAvailableCompanies($user);
    $activeCompanyId = $contextService->getActiveCompanyId($user);
    $branches = $contextService->getAvailableBranches($user, $activeCompanyId);
    $activeBranchId = $contextService->getActiveBranchId($user);
@endphp

@if(!$user?->isDirector() && $companies->isNotEmpty())
    <div class="flex items-center gap-1.5 sm:gap-2 mr-2" x-data="{
        companyId: '{{ $activeCompanyId }}',
        branchId: '{{ $activeBranchId }}',
        submitSwitch() {
            $refs.switchForm.submit();
        }
    }">
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
@endif
