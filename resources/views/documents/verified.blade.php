<x-guest-layout :title="__('Verifikasi Dokumen')" :heading="__('Status Dokumen')" :description="__('Hasil pemindaian QR Code Dokumen')" size="md">
    <div class="text-center">
        @if($document->is_expired)
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-error/20 text-error mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-base font-bold text-error mb-0.5">{{ __('Dokumen Kedaluwarsa') }}</h2>
            <p class="text-xs text-base-content/70 mb-5">{{ __('Masa berlaku dokumen ini telah habis pada :date.', ['date' => $document->expiration_date?->format('d M Y') ?? __('waktu yang ditentukan')]) }}</p>
        @elseif($document->currentVersion)
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-success/20 text-success mb-3 ring-4 ring-success/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-base font-bold text-success mb-0.5">{{ __('Dokumen Valid & Terverifikasi') }}</h2>
            <p class="text-xs text-base-content/70 mb-5">{{ __('Dokumen terdaftar resmi dan sah dalam sistem :app.', ['app' => config('app.name', 'DokuFlow')]) }}</p>
        @else
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warning/20 text-warning mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h2 class="text-base font-semibold text-warning mb-0.5">{{ __('Dokumen Belum Disetujui') }}</h2>
            <p class="text-xs text-base-content/70 mb-5">{{ __('Dokumen ada dalam sistem, namun belum memiliki versi yang disetujui.') }}</p>
        @endif

        {{-- Section 1: Document Details --}}
        <div class="bg-base-200/50 rounded-xl p-4 text-left border border-base-300 space-y-3">
            <div class="flex items-center justify-between border-b border-base-200 pb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Informasi Dokumen') }}
                </span>
                @if($document->currentVersion)
                    <span class="badge badge-sm badge-success badge-outline font-medium text-[11px]">
                        {{ __('Versi :v', ['v' => $document->currentVersion->version_number]) }}
                    </span>
                @endif
            </div>

            <dl class="space-y-2 text-[13px]">
                <div class="grid grid-cols-3 gap-2">
                    <dt class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Nomor') }}</dt>
                    <dd class="col-span-2 font-mono font-medium text-xs break-all">{{ $document->document_number ?? '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <dt class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Judul') }}</dt>
                    <dd class="col-span-2 font-semibold text-base-content">{{ $document->title }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <dt class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Unit Kerja') }}</dt>
                    <dd class="col-span-2 font-medium">{{ $document->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->kode_unit_kerja ?? '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <dt class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Pembuat') }}</dt>
                    <dd class="col-span-2 font-medium flex items-center gap-1.5">
                        <x-user-avatar :user="$document->owner" size="w-4 h-4" text-size="text-[9px]" />
                        <span>{{ $document->owner?->name ?? '-' }}</span>
                    </dd>
                </div>
                @if($document->currentVersion)
                <div class="grid grid-cols-3 gap-2">
                    <dt class="text-[11px] font-semibold text-base-content/50 uppercase">{{ __('Tanggal Terbit') }}</dt>
                    <dd class="col-span-2 font-medium text-base-content/80">{{ $document->currentVersion->created_at?->format('d M Y, H:i') ?? '-' }} WIB</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Section 2: Signing History Timeline (Mekari eSign Style) --}}
        @php
            $approvalSteps = $document->currentVersion?->approvalSteps?->sortBy('step_order');
            $allApproved = $document->currentVersion && (
                ($approvalSteps && $approvalSteps->isNotEmpty() && $approvalSteps->every(fn($s) => in_array($s->status, ['approved', 'bypassed'])))
                || $document->currentVersion->reviewed_at
            );
            $lastActionTime = $approvalSteps?->whereNotNull('action_at')->sortByDesc('action_at')->first()?->action_at 
                ?? $document->currentVersion?->reviewed_at 
                ?? $document->currentVersion?->created_at;

            // Helper function to resolve clean official role / position
            $resolveOfficialRole = function($step, $user, $document) {
                if ($step) {
                    $stepName = $step->step_name ?? '';
                    $assignedRole = $step->assigned_role ?? '';
                    $unitName = $user?->unitKerja?->nama_unit_kerja ?? $step->actionBy?->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->nama_unit_kerja;
                    $branchName = $document->branch?->name ?? $user?->branches?->first()?->name ?? '';

                    if (str_contains($stepName, 'Kepala Cabang') || str_contains($stepName, 'PJ Klinik') || $assignedRole === 'pic_klinik' || $user?->system_role === 'pic_klinik') {
                        return __('PJ Klinik / Kepala Cabang') . ($branchName ? (' • ' . $branchName) : '');
                    }
                    if (str_contains($stepName, 'Kepala Unit') || $assignedRole === 'kepala_unit_kerja' || $user?->system_role === 'kepala_unit_kerja') {
                        return __('Kepala Unit Kerja') . ($unitName ? (' • ' . $unitName) : '');
                    }
                    if ($assignedRole === 'direktur' || $user?->system_role === 'direktur') {
                        return __('Direktur Perusahaan');
                    }
                    if ($assignedRole === 'admin' || $user?->system_role === 'admin') {
                        return __('Administrator Dokumen');
                    }
                    
                    $cleanStep = preg_replace('/\s*\([^)]*\)$/', '', $stepName);
                    if ($cleanStep && !in_array(strtolower($cleanStep), ['approval', 'review', 'step'])) {
                        return $cleanStep . ($unitName ? (' • ' . $unitName) : '');
                    }
                }

                if ($user?->system_role) {
                    $unitName = $user->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->nama_unit_kerja;
                    return match($user->system_role) {
                        'pic_klinik' => __('PJ Klinik / Kepala Cabang') . ($document->branch?->name ? (' • ' . $document->branch->name) : ''),
                        'kepala_unit_kerja' => __('Kepala Unit Kerja') . ($unitName ? (' • ' . $unitName) : ''),
                        'direktur' => __('Direktur Perusahaan'),
                        'admin' => __('Administrator'),
                        default => ucfirst(str_replace('_', ' ', $user->system_role)) . ($unitName ? (' • ' . $unitName) : ''),
                    };
                }

                return null;
            };

            $timelineItems = [];

            // 1. Completed Node
            if ($allApproved) {
                $timelineItems[] = [
                    'type' => 'completed',
                    'action' => __('Digital signing completed'),
                    'by' => $document->owner?->name ?? __('System'),
                    'role' => __('Pengesahan Selesai & Terenkripsi Digital'),
                    'email' => $document->owner?->email,
                    'unit' => null,
                    'time' => $lastActionTime ? ($lastActionTime->format('d M Y \a\t H:i') . ' (WIB)') : null,
                    'status' => 'approved',
                ];
            }

            // 2. Each Approval / Signature Step
            if (isset($approvalSteps) && $approvalSteps->isNotEmpty()) {
                foreach ($approvalSteps->sortByDesc('step_order') as $step) {
                    $isApproved = $step->status === 'approved';
                    $isBypassed = $step->status === 'bypassed';
                    $isPending = $step->status === 'pending';
                    $isRejected = $step->status === 'rejected';
                    $user = $step->actionBy ?? $step->assignedUser ?? $step->signatureRequest?->targetUser;
                    $actionTime = $step->action_at ?? $step->signatureRequest?->responded_at;

                    $timelineItems[] = [
                        'type' => 'step',
                        'action' => $isApproved 
                            ? ($step->signature_request_id ? __('Signed') : __('Approved')) 
                            : ($isBypassed ? __('Auto-approved') : ($isPending ? __('Signature requested') : __('Rejected'))),
                        'by' => $user?->name ?? __('Pejabat Terkait'),
                        'role' => $resolveOfficialRole($step, $user, $document),
                        'email' => $user?->email,
                        'unit' => $user?->unitKerja?->nama_unit_kerja ?? $step->actionBy?->unitKerja?->nama_unit_kerja,
                        'time' => $actionTime ? ($actionTime->format('d M Y \a\t H:i') . ' (WIB)') : ($isPending ? __('Menunggu respon penandatangan') : null),
                        'status' => $step->status,
                    ];
                }
            } elseif ($document->currentVersion?->reviewer) {
                $revUser = $document->currentVersion->reviewer;
                $timelineItems[] = [
                    'type' => 'reviewer',
                    'action' => __('Approved'),
                    'by' => $revUser->name,
                    'role' => $resolveOfficialRole(null, $revUser, $document),
                    'email' => $revUser->email,
                    'unit' => $revUser->unitKerja?->nama_unit_kerja,
                    'time' => $document->currentVersion->reviewed_at ? ($document->currentVersion->reviewed_at->format('d M Y \a\t H:i') . ' (WIB)') : null,
                    'status' => 'approved',
                ];
            }

            // 3. Bottom Initial Creation Node
            $creator = $document->currentVersion?->author ?? $document->owner;
            $createdTime = $document->currentVersion?->created_at ?? $document->created_at;
            $creatorUnit = $document->unitKerja?->nama_unit_kerja ?? $creator?->unitKerja?->nama_unit_kerja;
            $timelineItems[] = [
                'type' => 'receive',
                'action' => __('Receive document'),
                'by' => $creator?->name ?? __('System'),
                'role' => __('Pembuat Dokumen') . ($creatorUnit ? (' • ' . $creatorUnit) : ''),
                'email' => $creator?->email,
                'unit' => $creatorUnit,
                'time' => $createdTime ? ($createdTime->format('d M Y \a\t H:i') . ' (WIB)') : null,
                'status' => 'receive',
            ];
        @endphp

        <div class="mt-4 bg-base-200/50 rounded-xl p-4 text-left border border-base-300">
            <div class="flex items-center justify-between border-b border-base-200 pb-2 mb-3.5">
                <span class="text-xs font-bold uppercase tracking-wider text-base-content/60 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Signing history') }}
                </span>
            </div>

            <div class="flex flex-col">
                @foreach($timelineItems as $index => $item)
                    @php
                        $isLast = $index === count($timelineItems) - 1;
                    @endphp
                    <div class="flex gap-3">
                        {{-- Column 1: Vertical Track & Node Dot (100% Perfectly Centered Line) --}}
                        <div class="w-5 flex flex-col items-center shrink-0">
                            @if($item['status'] === 'approved' || $item['type'] === 'completed')
                                <div class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px] font-bold shadow-xs shrink-0 z-10 ring-4 ring-base-200">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            @elseif($item['status'] === 'receive')
                                <div class="w-5 h-5 rounded-full bg-blue-600/15 flex items-center justify-center shrink-0 z-10 ring-4 ring-base-200">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                </div>
                            @elseif($item['status'] === 'pending')
                                <div class="w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center text-[8px] shrink-0 z-10 ring-4 ring-base-200">
                                    ⏳
                                </div>
                            @elseif($item['status'] === 'rejected')
                                <div class="w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0 z-10 ring-4 ring-base-200">
                                    ✕
                                </div>
                            @else
                                <div class="w-5 h-5 rounded-full bg-slate-400 text-white flex items-center justify-center text-[9px] font-bold shrink-0 z-10 ring-4 ring-base-200">
                                    ⚡
                                </div>
                            @endif

                            @if(!$isLast)
                                <div class="w-[2px] bg-base-300 flex-1 my-1 min-h-[22px]"></div>
                            @endif
                        </div>

                        {{-- Column 2: Content --}}
                        <div class="flex-1 text-xs {{ !$isLast ? 'pb-4' : 'pb-0' }} pt-0.5 space-y-1">
                            <div class="text-[13px] leading-snug">
                                <span class="font-bold text-base-content">{{ $item['action'] }}</span>
                                <span class="text-base-content/60 font-normal">{{ $item['type'] === 'receive' ? __('from') : __('by') }}</span>
                                <span class="font-semibold text-base-content">{{ $item['by'] }}</span>
                            </div>

                            {{-- Official Role / Jabatan Resmi Penandatangan --}}
                            @if(!empty($item['role']))
                                <div class="text-primary font-medium text-[11px] flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-primary/75 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $item['role'] }}
                                    </span>
                                </div>
                            @endif

                            {{-- Email --}}
                            @if(!empty($item['email']))
                                <div class="text-base-content/50 text-[11px] font-mono break-all">
                                    {{ $item['email'] }}
                                </div>
                            @endif

                            {{-- Timestamp & Verification Badge --}}
                            @if(!empty($item['time']))
                                <div class="text-base-content/50 text-[11px] pt-0.5 flex items-center gap-1.5 flex-wrap">
                                    <span>{{ $item['time'] }}</span>
                                    @if($item['status'] === 'approved' && $item['type'] === 'step')
                                        <span class="badge badge-success/15 text-success border border-success/20 text-[9px] font-semibold px-1.5 py-0 h-4">
                                            {{ __('Tanda Tangan Terverifikasi') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        {{-- Section 3: Action Buttons --}}
        <div class="mt-5 flex flex-col sm:flex-row gap-2">
            <a href="{{ route('documents.hash.preview', ['token' => $token ?? request()->route('token')]) }}" class="btn btn-primary btn-sm flex-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ __('Lihat Dokumen') }}
            </a>
            <a href="/" class="btn btn-outline btn-sm flex-1">{{ __('Beranda') }}</a>
        </div>
    </div>
</x-guest-layout>
