{{-- Modal: Document Audit Trail & Signer Verification History --}}
<dialog id="audit-trail-modal" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs text-base-content">
    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-2xl sm:max-w-3xl text-base-content max-h-[90vh] flex flex-col">
        {{-- Header --}}
        <div class="p-5 sm:p-6 pb-4 border-b border-base-200 bg-base-200/30">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 ring-4 ring-primary/5 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-base-content leading-tight">{{ __('Audit Trail & Riwayat Pengesahan') }}</h3>
                        <p class="text-xs text-base-content/60 mt-0.5">
                            {{ $document->document_number ?? '-' }} • <span class="font-medium text-base-content/80">{{ $document->title }}</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('audit-trail-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                    ✕
                </button>
            </div>

            {{-- Summary & Compliance Info Bar --}}
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <div class="p-2.5 rounded-xl bg-base-100 border border-base-300/60 shadow-2xs">
                    <div class="text-[10px] uppercase font-semibold text-base-content/50">{{ __('Status Saat Ini') }}</div>
                    <div class="font-bold text-base-content mt-0.5">
                        @if($document->currentVersion)
                            <span class="text-secondary font-semibold">{{ __('Aktif (v:v)', ['v' => $document->currentVersion->version_number]) }}</span>
                        @elseif($pendingVersion)
                            <span class="text-primary font-semibold">{{ __('Menunggu (v:v)', ['v' => $pendingVersion->version_number]) }}</span>
                        @else
                            <span class="text-base-content/70">{{ __('Draf') }}</span>
                        @endif
                    </div>
                </div>
                <div class="p-2.5 rounded-xl bg-base-100 border border-base-300/60 shadow-2xs">
                    <div class="text-[10px] uppercase font-semibold text-base-content/50">{{ __('Total Riwayat') }}</div>
                    <div class="font-bold text-base-content mt-0.5">
                        {{ count($auditTrail ?? []) }} {{ __('Aktivitas') }}
                    </div>
                </div>
                <div class="p-2.5 rounded-xl bg-base-100 border border-base-300/60 shadow-2xs">
                    <div class="text-[10px] uppercase font-semibold text-base-content/50">{{ __('Unit Kerja') }}</div>
                    <div class="font-bold text-base-content mt-0.5 truncate" title="{{ $document->unitKerja?->nama_unit_kerja ?? '-' }}">
                        {{ $document->unitKerja?->kode_unit_kerja ?? '-' }} - {{ $document->unitKerja?->nama_unit_kerja ?? 'Umum' }}
                    </div>
                </div>
                <div class="p-2.5 rounded-xl bg-base-100 border border-base-300/60 shadow-2xs">
                    <div class="text-[10px] uppercase font-semibold text-base-content/50">{{ __('Integritas Digital') }}</div>
                    <div class="font-bold text-primary mt-0.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <span>{{ __('Terverifikasi') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Body: Chronological Audit Events Timeline --}}
        <div class="p-5 sm:p-6 overflow-y-auto max-h-[60vh] space-y-4">
            @if(empty($auditTrail) || $auditTrail->isEmpty())
                <div class="text-center py-10 text-base-content/50">
                    <svg class="w-12 h-12 mx-auto text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="font-semibold text-sm">{{ __('Belum ada riwayat audit tercatat.') }}</p>
                    <p class="text-xs text-base-content/40 mt-0.5">{{ __('Aktivitas pengesahan, perubahan versi, dan tanda tangan akan muncul di sini.') }}</p>
                </div>
            @else
                <div class="flex flex-col">
                    @foreach($auditTrail as $index => $item)
                        @php
                            $isLast = $loop->last;
                            $action = $item['action'] ?? '';
                            $isApproved = str_contains($action, 'approved');
                            $isRejected = str_contains($action, 'rejected');
                            $isBypassed = str_contains($action, 'bypassed');
                            $isRollback = str_contains($action, 'rollback');
                            $isRename = str_contains($action, 'rename');
                            $isRevision = str_contains($action, 'revision');
                            $isCreate = str_contains($action, 'created') || str_contains($action, 'uploaded') || $isRevision;

                            // Determine clean concise title and badge
                            $eventTitle = match(true) {
                                $action === 'document.created' => __('Dokumen Dibuat (v:v)', ['v' => $item['version_number'] ?? '1.0']),
                                $action === 'version.revision_submitted' => __('Pengajuan Revisi Dokumen (v:v)', ['v' => $item['version_number'] ?? '2.0']),
                                $action === 'version.uploaded' => __('Berkas Pengganti Diunggah (v:v)', ['v' => $item['version_number'] ?? '1.0']),
                                $action === 'version.created' => __('Pengajuan Versi Baru (v:v)', ['v' => $item['version_number'] ?? '1.0']),
                                $action === 'approval_step.approved' => __('Persetujuan & Tanda Tangan: :name', ['name' => $item['metadata']['step_name'] ?? __('Tahap Disetujui')]),
                                $action === 'version.approved' => __('Dokumen & Versi Disetujui (v:v)', ['v' => $item['version_number'] ?? '1.0']),
                                $action === 'approval_step.rejected' => __('Pengesahan Ditolak: :name', ['name' => $item['metadata']['step_name'] ?? __('Tahap Ditolak')]),
                                $action === 'version.rejected' => __('Pengajuan Versi Ditolak (v:v)', ['v' => $item['version_number'] ?? '1.0']),
                                $action === 'approval_step.bypassed' => __('Dilewati Otomatis (Pembuat): :name', ['name' => $item['metadata']['step_name'] ?? 'Bypass']),
                                $action === 'rollback.requested' => __('Permintaan Rollback Diajukan'),
                                $action === 'rollback.approved' => __('Permintaan Rollback Disetujui'),
                                $action === 'rollback.rejected' => __('Permintaan Rollback Ditolak'),
                                $action === 'document.rename_requested' => __('Pengajuan Ubah Nama Dokumen (v:v)', ['v' => $item['version_number'] ?? '']),
                                $action === 'document.rename_approved' => __('Perubahan Nama Dokumen Disetujui'),
                                $action === 'document.rename_rejected' => __('Perubahan Nama Dokumen Ditolak'),
                                $action === 'version.discarded' => __('Versi Dokumen Dibatalkan / Dibuang'),
                                default => ucfirst(str_replace(['.', '_'], ' ', $action)),
                            };

                            // Clean title to avoid duplicated parentheses
                            $eventTitle = preg_replace('/\s*\([^)]*\)$/', '', $eventTitle);

                            $badgeClass = match(true) {
                                $isApproved => 'badge-secondary text-white',
                                $isRejected => 'badge-error text-white',
                                $isRollback => 'badge-primary text-white',
                                $isRename => 'badge-primary/15 text-primary border border-primary/20',
                                $isRevision => 'badge-primary text-white',
                                $isCreate => 'badge-primary text-white',
                                default => 'badge-neutral',
                            };

                            $badgeText = match(true) {
                                $action === 'document.created' => __('Dibuat'),
                                $action === 'version.revision_submitted' => __('Revisi'),
                                $action === 'version.uploaded' => __('Upload Berkas'),
                                $isApproved => __('Disetujui'),
                                $isRejected => __('Ditolak'),
                                $isBypassed => __('Bypass'),
                                $action === 'rollback.requested' => __('Request Rollback'),
                                $action === 'rollback.approved' => __('Rollback Selesai'),
                                $action === 'rollback.rejected' => __('Rollback Ditolak'),
                                $action === 'document.rename_requested' => __('Pengajuan Nama'),
                                $action === 'document.rename_approved' => __('Nama Berubah'),
                                $action === 'document.rename_rejected' => __('Nama Ditolak'),
                                $isCreate => ($item['version_number'] ? ('v' . $item['version_number']) : __('Versi Baru')),
                                default => __('Aktivitas'),
                            };
                        @endphp

                        <div class="flex gap-3.5 sm:gap-4">
                            {{-- Node Icon & Vertical Connector --}}
                            <div class="w-7 flex flex-col items-center shrink-0">
                                {{-- Top Connector Line --}}
                                @if(!$loop->first)
                                    <div class="w-0.5 bg-base-300 h-2.5 shrink-0"></div>
                                @else
                                    <div class="h-2.5 shrink-0"></div>
                                @endif

                                {{-- Node Circle --}}
                                @if($isApproved || $isBypassed)
                                    <div class="w-7 h-7 rounded-full bg-secondary text-white flex items-center justify-center shrink-0 shadow-xs ring-4 ring-secondary/15 z-10">
                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                @elseif($isRejected)
                                    <div class="w-7 h-7 rounded-full bg-error text-white flex items-center justify-center shrink-0 ring-4 ring-error/20 shadow-xs z-10">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                @elseif($isRollback)
                                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center shrink-0 ring-4 ring-primary/15 shadow-xs z-10">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                @elseif($isRename)
                                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center shrink-0 ring-4 ring-primary/15 shadow-xs z-10">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-7 h-7 rounded-full bg-primary text-white flex items-center justify-center shrink-0 ring-4 ring-primary/15 shadow-xs z-10">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                @endif

                                {{-- Bottom Connector Line --}}
                                @if(!$isLast)
                                    <div class="w-0.5 bg-base-300 flex-1 my-0.5 min-h-[26px]"></div>
                                @endif
                            </div>

                            {{-- Event Content Card --}}
                            <div class="flex-1 {{ !$isLast ? 'pb-4' : 'pb-1' }} min-w-0">
                                <div class="p-3.5 rounded-2xl bg-base-200/40 border border-base-300/60 hover:bg-base-200/60 transition-colors">
                                    {{-- Row 1: Title & Badge --}}
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <h4 class="font-bold text-sm text-base-content leading-tight">
                                            {{ $eventTitle }}
                                        </h4>
                                        <span class="badge {{ $badgeClass }} badge-sm font-semibold text-[10px] py-0 px-2 h-5">
                                            {{ $badgeText }}
                                        </span>
                                    </div>

                                    {{-- Row 2: Actor Info --}}
                                    <div class="text-xs text-base-content/80 mt-1.5 flex items-center gap-2 flex-wrap">
                                        <div class="flex items-center gap-1.5">
                                            <x-user-avatar :user="$item['actor'] ?? null" :name="$item['actor_name']" size="w-4 h-4" text-size="text-[9px]" />
                                            <span class="font-semibold text-base-content">{{ $item['actor_name'] }}</span>
                                        </div>
                                        @if(!empty($item['actor_unit']))
                                            <span class="text-base-content/40">•</span>
                                            <span class="text-base-content/70">{{ $item['actor_unit'] }}</span>
                                        @elseif(!empty($item['actor_role']))
                                            <span class="text-base-content/40">•</span>
                                            <span class="text-base-content/70">{{ ucfirst(str_replace('_', ' ', $item['actor_role'])) }}</span>
                                        @endif
                                    </div>

                                    {{-- Row 3: Security, Device & Timestamp Metadata --}}
                                    <div class="mt-2 pt-2 border-t border-base-300/40 flex items-center gap-1.5 flex-wrap text-[11px] text-base-content/60">
                                        <span class="flex items-center gap-1 text-base-content/75 font-medium">
                                            <svg class="w-3 h-3 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ $item['timestamp']->format('d M Y, H:i:s') }} WIB
                                        </span>

                                        @if(!empty($item['ip_address']))
                                            <span class="badge badge-ghost badge-xs font-mono text-[10px] px-1.5 py-0 h-4" title="{{ __('Alamat IP saat menandatangani / memproses') }}">
                                                IP: {{ $item['ip_address'] }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost badge-xs text-[10px] px-1.5 py-0 h-4">
                                                IP: {{ __('Terverifikasi Sistem') }}
                                            </span>
                                        @endif

                                        @if(!empty($item['metadata']['signature_request_id']) || $isApproved)
                                            <span class="badge badge-primary/15 text-primary border border-primary/20 badge-xs font-semibold text-[10px] px-1.5 py-0 h-4">
                                                {{ __('Tanda Tangan Digital') }}
                                            </span>
                                        @endif

                                        @if(!empty($item['user_agent']))
                                            @php
                                                $deviceInfo = \App\Services\AuditService::parseDevice($item['user_agent']);
                                            @endphp
                                            @if(!empty($deviceInfo))
                                                <span class="badge badge-ghost badge-xs text-[10px] px-1.5 py-0 h-4 flex items-center gap-1 font-normal opacity-90" title="{{ $item['user_agent'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $deviceInfo }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>

                                    {{-- Row 4: File Attachment Info (if version upload) --}}
                                    @if(!empty($item['metadata']['file_original_name']))
                                        <div class="mt-2.5 p-2 rounded-xl bg-base-100 border border-base-300/80 text-xs flex items-center gap-1.5 text-base-content/80">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <span class="font-semibold text-base-content">{{ __('Berkas Diunggah:') }}</span>
                                            <span class="font-mono text-base-content/90 font-medium">{{ $item['metadata']['file_original_name'] }}</span>
                                        </div>
                                    @endif

                                    {{-- Row 5: Rename Details (if rename event) --}}
                                    @if(!empty($item['metadata']['old_title']))
                                        <div class="mt-2.5 p-2 rounded-xl bg-primary/10 border border-primary/20 text-xs text-primary flex items-center gap-1.5">
                                            <span class="font-semibold">{{ __('Nama Semula:') }}</span>
                                            <span class="line-through text-base-content/60">{{ $item['metadata']['old_title'] }}</span>
                                        </div>
                                    @endif

                                    {{-- Row 6: Notes / Feedback (Approval notes, Rejection reasons) --}}
                                    @if(!empty($item['notes']))
                                        <div class="mt-2.5 p-2 rounded-xl {{ $isRejected ? 'bg-error/10 border border-error/20 text-error' : 'bg-base-100 border border-base-300' }} text-xs">
                                            <span class="font-bold">{{ $isRejected ? __('Catatan Penolakan:') : __('Catatan:') }}</span>
                                            <span class="text-base-content/90 font-normal ml-1">{{ $item['notes'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="p-4 sm:p-5 border-t border-base-200 bg-base-200/20 flex items-center justify-between gap-3">
            <div class="text-[11px] text-base-content/50 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                <span>{{ __('Rekam jejak audit dienkripsi dan diproteksi dari modifikasi.') }}</span>
            </div>
            <button type="button" onclick="document.getElementById('audit-trail-modal').close()" class="btn btn-ghost btn-sm rounded-xl px-4 font-medium">
                {{ __('Tutup') }}
            </button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button type="button" onclick="document.getElementById('audit-trail-modal').close()">{{ __('Tutup') }}</button>
    </form>
</dialog>
