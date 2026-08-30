<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-base-content leading-tight">
            {{ __('Persetujuan & Riwayat TTD') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        @if(session('success'))
            <div class="alert alert-success shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Incoming Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            {{ __('Incoming Requests (Your Signature Approval)') }}
                        </h3>
                        <p class="text-sm text-base-content/60">
                            {{ __('List of users requesting to use your signature in their documents.') }}
                        </p>
                    </div>
                </div>

                @if($incomingRequests->isEmpty())
                    <div class="py-8 text-center text-base-content/40 space-y-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-sm">{{ __('No incoming requests at this time.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('Signature Requester') }}</th>
                                    <th>{{ __('Dokumen') }}</th>
                                    <th>{{ __('Request Time') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incomingRequests as $req)
                                    <tr class="hover:bg-base-200/50">
                                        <td>
                                            <div class="font-bold text-sm">{{ $req->requester->name }}</div>
                                            <div class="text-xs text-base-content/50">{{ $req->requester->email }}</div>
                                        </td>
                                        <td>
                                            @if($req->document)
                                                <a href="{{ route('documents.show', $req->document) }}" class="font-medium text-sm text-base-content no-underline hover:text-primary transition-colors">
                                                    {{ $req->document->title }}
                                                </a>
                                                <div class="text-xs font-mono text-base-content/50">{{ $req->document->document_number }}</div>
                                            @else
                                                <span class="text-xs italic text-base-content/40">{{ __('General Document / Context') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-base-content/70">
                                            {{ $req->requested_at ? $req->requested_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td>
                                            @if($req->isApproved())
                                                <span class="badge badge-success gap-1 text-xs">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('Approved') }}
                                                </span>
                                            @elseif($req->isRejected())
                                                <span class="badge badge-error gap-1 text-xs">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Rejected') }}
                                                </span>
                                            @else
                                                <span class="badge badge-warning gap-1 text-xs animate-pulse">
                                                    ⏳ {{ __('Pending') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($req->isPending())
                                                <div class="flex items-center justify-end gap-2">
                                                    <form method="POST" action="{{ route('signatures.requests.approve', $req) }}" onsubmit="document.getElementById('loading-modal').showModal()">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-xs gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                            {{ __('Approve') }}
                                                        </button>
                                                    </form>

                                                    <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1">
                                                        {{ __('Reject') }}
                                                    </button>
                                                </div>

                                                {{-- Reject Reason Modal --}}
                                                <dialog id="reject-modal-{{ $req->id }}" class="modal text-left">
                                                    <div class="modal-box">
                                                        <h3 class="font-bold text-lg">{{ __('Reject Signature Request') }}</h3>
                                                        <p class="py-2 text-sm text-base-content/70">
                                                            {!! __('Reject use of your signature by :name.', ['name' => '<strong>'.$req->requester->name.'</strong>']) !!}
                                                        </p>
                                                        <form method="POST" action="{{ route('signatures.requests.reject', $req) }}">
                                                            @csrf
                                                            <div class="form-control mb-4">
                                                                <label class="label">
                                                                    <span class="label-text font-medium">{{ __('Rejection Reason (Optional)') }}</span>
                                                                </label>
                                                                <textarea name="reason" class="textarea textarea-bordered w-full" rows="3" placeholder="{{ __('Enter rejection reason...') }}"></textarea>
                                                            </div>
                                                            <div class="modal-action">
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').close()" class="btn btn-ghost">{{ __('Batal') }}</button>
                                                                <button type="submit" class="btn btn-error">{{ __('Reject Request') }}</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </dialog>
                                            @else
                                                <span class="text-xs text-base-content/40 italic">{{ __('Done') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $incomingRequests->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Outgoing Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            {{ __('My Request History') }}
                        </h3>
                        <p class="text-sm text-base-content/60">
                            {{ __('List of other users\' signatures you requested for use in your documents.') }}
                        </p>
                    </div>
                </div>

                @if($outgoingRequests->isEmpty())
                    <div class="py-8 text-center text-base-content/40 space-y-2">
                        <p class="text-sm">{{ __('You have never requested another user\'s signature approval.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('Target Signature Owner') }}</th>
                                    <th>{{ __('Dokumen') }}</th>
                                    <th>{{ __('Request Time') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outgoingRequests as $req)
                                    <tr class="hover:bg-base-200/50">
                                        <td>
                                            <div class="font-bold text-sm">{{ $req->targetUser->name }}</div>
                                            <div class="text-xs text-base-content/50">{{ $req->targetUser->email }}</div>
                                        </td>
                                        <td>
                                            @if($req->document)
                                                <a href="{{ route('documents.show', $req->document) }}" class="font-medium text-sm text-base-content no-underline hover:text-primary transition-colors">
                                                    {{ $req->document->title }}
                                                </a>
                                                <div class="text-xs font-mono text-base-content/50">{{ $req->document->document_number }}</div>
                                            @else
                                                <span class="text-xs italic text-base-content/40">{{ __('Dokumen Umum') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-base-content/70">
                                            {{ $req->requested_at ? $req->requested_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td>
                                            @if($req->isApproved())
                                                <span class="badge badge-success gap-1 text-xs">{{ __('Approved') }}</span>
                                            @elseif($req->isRejected())
                                                <span class="badge badge-error gap-1 text-xs">{{ __('Rejected') }}</span>
                                            @else
                                                <span class="badge badge-warning gap-1 text-xs">⏳ {{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-base-content/60">
                                            {{ $req->rejected_reason ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $outgoingRequests->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Loading Modal --}}
    <dialog id="loading-modal" class="modal">
        <div class="modal-box flex flex-col items-center justify-center py-10">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="font-bold text-lg mt-4">{{ __('Memproses Dokumen...') }}</h3>
            <p class="text-sm text-base-content/70 mt-2 text-center">{{ __('Harap tunggu sebentar, sistem sedang membubuhkan tanda tangan Anda ke dalam dokumen secara otomatis.') }}</p>
        </div>
    </dialog>
</x-app-layout>
