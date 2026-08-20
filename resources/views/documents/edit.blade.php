<x-app-layout>

    @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
        <x-confirm-modal
            name="confirm-discard-{{ $document->id }}"
            title="Discard Document?"
            message="Are you sure you want to discard this document and all its changes?"
            :action="route('documents.destroy', $document)"
            method="DELETE"
            confirmLabel="Discard"
        />

        <x-confirm-modal
            name="confirm-discard-version-{{ $document->id }}"
            title="Discard Changes?"
            message="Are you sure you want to discard the pending changes? The approved version will remain intact."
            :action="route('documents.discard', $document)"
            method="POST"
            confirmLabel="Discard Changes"
        />
    @endif

    @php
        $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
        $hasDraftOnly = !$pending && !$document->currentVersion;
    @endphp

    <div class="h-[calc(100vh-4rem)] flex flex-col bg-base-200/50">

        {{-- Top Navigation & Action Bar --}}
        <div class="bg-base-100 border-b border-base-300 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm btn-square" title="{{ __('Kembali') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold truncate">{{ $document->title }}</h1>
                        @if($document->document_number)
                            <span class="badge badge-ghost badge-sm hidden sm:inline-flex">
                                {{ $document->document_number }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/60">
                        {{ __('ONLYOFFICE Document Editor') }} &bull; v{{ $version->version_number }}
                        @if($pending)
                            <span class="badge badge-warning badge-xs ml-1">{{ __('Pending Review') }}</span>
                        @elseif($hasDraftOnly)
                            <span class="badge badge-info badge-xs ml-1">{{ __('Draft') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('documents.download', $document) }}" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ __('Download DOCX') }}
                </a>

                @if($document->currentVersion)
                    @can('update', $document)
                        @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
                            <button type="button" class="btn btn-outline btn-error btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-version-{{ $document->id }}')">
                                {{ __('Discard Changes') }}
                            </button>
                        @else
                            <form method="POST" action="{{ route('documents.discard', $document) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-error btn-sm">
                                    {{ __('Discard Changes') }}
                                </button>
                            </form>
                        @endif
                    @endcan
                @else
                    @can('delete', $document)
                        @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
                            <button type="button" class="btn btn-outline btn-error btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')">
                                {{ __('Discard') }}
                            </button>
                        @else
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-error btn-sm">
                                    {{ __('Discard') }}
                                </button>
                            </form>
                        @endif
                    @endcan
                @endif

                <a href="{{ route('documents.show', $document) }}" class="btn btn-primary btn-sm px-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Selesai Edit') }}
                </a>
            </div>
        </div>

        {{-- Pending Alert if exists --}}
        @if($pending)
            <div class="px-4 py-2 bg-warning/10 border-b border-warning/20 text-xs text-warning-content flex items-center justify-between">
                <span>{{ __('Terdapat versi pending (v:version) yang menunggu review. Setiap perubahan yang Anda simpan akan memperbarui versi pending ini.', ['version' => $pending->version_number]) }}</span>
            </div>
        @endif

        {{-- ONLYOFFICE Editor Viewport --}}
        <div class="flex-1 w-full h-full relative overflow-hidden bg-base-100">
            <div id="onlyoffice-editor-container" class="w-full h-full"></div>
            
            <div id="onlyoffice-fallback" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 bg-base-100/95 z-20 text-center">
                <div class="max-w-md p-6 bg-base-200 rounded-2xl border border-base-300 shadow-xl">
                    <svg class="w-12 h-12 text-warning mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="font-bold text-lg mb-2">{{ __('ONLYOFFICE Server Belum Aktif') }}</h3>
                    <p class="text-sm text-base-content/70 mb-4">
                        {{ __('Tidak dapat terhubung ke server ONLYOFFICE di') }} <code class="bg-base-300 px-1 py-0.5 rounded">{{ config('onlyoffice.url') }}</code>.
                        <br>{{ __('Pastikan container Docker ONLYOFFICE sedang berjalan dengan:') }}
                    </p>
                    <pre class="bg-neutral text-neutral-content p-3 rounded-lg text-xs text-left mb-4 overflow-x-auto"><code>docker compose up -d</code></pre>
                    <div class="flex justify-center gap-2">
                        <button onclick="window.location.reload()" class="btn btn-primary btn-sm">{{ __('Muat Ulang Halaman') }}</button>
                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline btn-sm">{{ __('Download DOCX') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
                onerror="document.getElementById('onlyoffice-fallback').classList.remove('hidden');"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof DocsAPI === 'undefined') {
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                    return;
                }

                try {
                    const config = @json($onlyOfficeConfig);
                    window.docEditor = new DocsAPI.DocEditor("onlyoffice-editor-container", config);
                } catch (e) {
                    console.error('ONLYOFFICE initialization error:', e);
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                }
            });
        </script>
    @endpush

</x-app-layout>