<x-app-layout>
    <x-slot name="header">{{ __('Ganti Gambar Tanda Tangan / Stempel') }}</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="avatar">
                            <div class="w-20 h-20 rounded-xl bg-base-200 p-2 border border-base-300 shadow-inner flex items-center justify-center overflow-hidden"
                                 style="background-image: repeating-conic-gradient(#f3f4f6 0% 25%, #ffffff 0% 50%); background-size: 12px 12px;">
                                <img src="{{ $signature->url }}" alt="Signature" class="object-contain max-w-full max-h-full drop-shadow-xs" />
                            </div>
                        </div>
                        <div>
                            <div class="font-medium">{{ $signature->user->name ?? '-' }}</div>
                            <div class="text-xs text-base-content/50">{{ $signature->user->email ?? '' }}</div>
                            <span class="badge {{ $signature->type === 'company_stamp' ? 'badge-secondary' : 'badge-ghost' }} badge-sm font-semibold mt-1">
                                {{ $signature->type === 'company_stamp' ? __('Stempel Perusahaan') : __('Tanda Tangan') }}
                                @if($signature->company) — {{ $signature->company->name }} @endif
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('admin.signatures.update', $signature) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="form-control">
                            <label class="label"><span class="label-text">{{ __('Gambar Pengganti') }}</span></label>
                            <input type="file" name="signature_image" accept="image/png,image/jpeg" class="file-input file-input-bordered w-full" required />
                            <label class="label">
                                <span class="label-text-alt text-base-content/50">{{ __('PNG/JPG, maksimal 2MB.') }}</span>
                            </label>
                            @error('signature_image') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <a href="{{ route('admin.signatures.index') }}" class="btn btn-ghost btn-sm">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Simpan Perubahan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
