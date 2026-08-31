<x-app-layout>
    <x-slot name="header">
        <h2 class="font-light text-3xl tracking-tight text-base-content">{{ __('Inbox Persetujuan') }}</h2>
    </x-slot>

    <div class="py-4 md:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 w-full space-y-8">
            @if(session('success'))
                <div class="alert alert-success shadow-sm rounded-lg border-0 bg-success/10 text-success-content">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-base-100/50 backdrop-blur-xl border border-base-200/50 rounded-2xl overflow-hidden shadow-sm">
                <!-- Header Tabs / Info -->
                <div class="border-b border-base-200/50 px-6 py-4 flex items-center justify-between bg-base-100">
                    <h3 class="font-medium text-base-content/80">{{ __('Tugas Menunggu') }}</h3>
                    <div class="flex gap-2">
                        @if($pendingRollbacks->count())
                            <span class="badge badge-error badge-sm">{{ $pendingRollbacks->count() }} {{ __('Rollbacks') }}</span>
                        @endif
                        <span class="badge badge-primary badge-sm">{{ $pendingVersions->count() }} {{ __('Dokumen') }}</span>
                    </div>
                </div>

                <div class="divide-y divide-base-200/50">
                    {{-- Rollbacks --}}
                    @foreach($pendingRollbacks as $doc)
                        <div class="group flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 sm:px-6 transition-colors hover:bg-base-200/40 cursor-default">
                            <div class="flex items-start gap-4 min-w-0 flex-1">
                                <div class="mt-1">
                                    <div class="w-2 h-2 rounded-full bg-error ring-4 ring-error/20"></div>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <p class="font-medium text-base-content truncate">{{ $doc->title }}</p>
                                        <span class="badge badge-outline border-error/30 text-error text-[10px] uppercase font-bold px-1.5 h-4">{{ __('Rollback v' . $doc->pendingRollbackVersion->version_number) }}</span>
                                    </div>
                                    <p class="text-xs text-base-content/50 truncate">
                                        {{ __('Diminta oleh') }} {{ $doc->rollbackRequestedBy?->name ?? '—' }} &bull; {{ $doc->rollback_requested_at?->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-1 mt-3 sm:mt-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity focus-within:opacity-100">
                                <a href="{{ route('documents.preview', ['document' => $doc, 'from' => 'approvals']) }}" class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-primary hover:bg-primary/10" title="{{ __('Pratinjau') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </a>
                                <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').showModal()" class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-error hover:bg-error/10" title="{{ __('Tolak') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>

                                {{-- Reject Rollback Modal --}}
                                <dialog id="reject-rollback-modal-{{ $doc->id }}" class="modal text-left">
                                    <div class="modal-box">
                                        <h3 class="font-bold text-lg text-base-content">{{ __('Tolak Permintaan Rollback') }}</h3>
                                        <p class="py-2 text-sm text-base-content/70">
                                            {!! __('Tolak permintaan rollback dokumen :doc ke versi v:ver?', ['doc' => '<strong>'.$doc->title.'</strong>', 'ver' => '<strong>'.$doc->pendingRollbackVersion->version_number.'</strong>']) !!}
                                        </p>
                                        <form method="POST" action="{{ route('approvals.rollback-request.reject', $doc) }}">
                                            @csrf
                                            <div class="form-control mb-4">
                                                <label class="label">
                                                    <span class="label-text font-medium">{{ __('Catatan / Alasan Penolakan (Opsional)') }}</span>
                                                </label>
                                                <textarea name="notes" class="textarea textarea-bordered w-full text-sm" rows="3" placeholder="{{ __('Tuliskan alasan penolakan untuk pemohon...') }}"></textarea>
                                            </div>
                                            <div class="modal-action">
                                                <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost">{{ __('Batal') }}</button>
                                                <button type="submit" class="btn btn-error text-white">{{ __('Tolak Rollback') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>{{ __('Batal') }}</button>
                                    </form>
                                </dialog>
                                <form method="POST" action="{{ route('approvals.rollback-request.approve', $doc) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-success hover:bg-success/10" title="{{ __('Setujui') }}" onclick="return confirm('{{ __('Setujui rollback ke v:version?', ['version' => $doc->pendingRollbackVersion->version_number]) }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    {{-- Regular Approvals --}}
                    @forelse($pendingVersions as $version)
                        <div class="group flex flex-col sm:flex-row items-start justify-between p-4 sm:px-6 transition-colors hover:bg-base-200/40 cursor-default">
                            <div class="flex items-start gap-4 min-w-0 flex-1">
                                <div class="mt-1">
                                    <div class="w-2 h-2 rounded-full bg-primary ring-4 ring-primary/20"></div>
                                </div>
                                <div class="min-w-0 w-full pr-4">
                                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                        <p class="font-medium text-base-content truncate max-w-full">{{ $version->document->title }}</p>
                                        <span class="badge badge-outline border-primary/30 text-primary text-[10px] uppercase font-bold px-1.5 h-4">v{{ $version->version_number }}</span>
                                    </div>
                                    <p class="text-xs text-base-content/50 truncate">
                                        {{ $version->author_name }} &bull; {{ $version->created_at->diffForHumans() }}
                                    </p>
                                    
                                    @if(strip_tags($version->content))
                                        <div class="mt-2 text-sm text-base-content/70 line-clamp-2 max-w-2xl bg-base-100/50 p-2 rounded border border-base-200/50">
                                            {!! strip_tags($version->content) !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-1 mt-3 sm:mt-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity focus-within:opacity-100 shrink-0">
                                <a href="{{ route('documents.preview', ['document' => $version->document, 'from' => 'approvals']) }}" class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-primary hover:bg-primary/10" title="{{ __('Pratinjau') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                </a>
                                <button type="button" onclick="document.getElementById('reject-version-modal-{{ $version->id }}').showModal()" class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-error hover:bg-error/10" title="{{ __('Tolak') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>

                                {{-- Reject Version Modal --}}
                                <dialog id="reject-version-modal-{{ $version->id }}" class="modal text-left">
                                    <div class="modal-box">
                                        <h3 class="font-bold text-lg text-base-content">{{ __('Tolak Dokumen') }}</h3>
                                        <p class="py-2 text-sm text-base-content/70">
                                            {!! __('Tolak dokumen :doc (v:ver) yang diajukan oleh :author?', ['doc' => '<strong>'.$version->document->title.'</strong>', 'ver' => '<strong>'.$version->version_number.'</strong>', 'author' => '<strong>'.$version->author_name.'</strong>']) !!}
                                        </p>
                                        <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}">
                                            @csrf
                                            <div class="form-control mb-4">
                                                <label class="label">
                                                    <span class="label-text font-medium">{{ __('Catatan / Alasan Penolakan (Opsional)') }}</span>
                                                </label>
                                                <textarea name="notes" class="textarea textarea-bordered w-full text-sm" rows="3" placeholder="{{ __('Tuliskan catatan atau masukan perbaikan...') }}"></textarea>
                                            </div>
                                            <div class="modal-action">
                                                <button type="button" onclick="document.getElementById('reject-version-modal-{{ $version->id }}').close()" class="btn btn-ghost">{{ __('Batal') }}</button>
                                                <button type="submit" class="btn btn-error text-white">{{ __('Tolak Dokumen') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>{{ __('Batal') }}</button>
                                    </form>
                                </dialog>
                                <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-success hover:bg-success/10" title="{{ __('Setujui') }}" onclick="return confirm('{{ __('Setujui dokumen ini?') }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        @if(!$pendingRollbacks->count())
                            <div class="p-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-base-200/50 mb-4 text-base-content/30">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-medium text-base-content">{{ __('Inbox Kosong') }}</h3>
                                <p class="text-sm text-base-content/50 mt-1">{{ __('Tidak ada dokumen yang memerlukan persetujuan.') }}</p>
                            </div>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
