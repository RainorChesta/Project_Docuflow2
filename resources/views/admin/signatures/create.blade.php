<x-app-layout>
    <x-slot name="header">{{ __('Tambah Tanda Tangan / Stempel') }}</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.signatures.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">{{ __('Pengguna') }}</span></label>
                            <select name="user_id" class="select select-bordered w-full" required>
                                <option value="">{{ __('-- Pilih Pengguna --') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (string)old('user_id', $selectedUserId ?? '') === (string)$user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">{{ __('Tipe') }}</span></label>
                            <select name="type" id="signature-type" class="select select-bordered w-full" required>
                                <option value="original" {{ old('type', $selectedType ?? 'original') === 'original' ? 'selected' : '' }}>{{ __('Tanda Tangan Asli') }}</option>
                                <option value="company_stamp" {{ old('type', $selectedType ?? 'original') === 'company_stamp' ? 'selected' : '' }}>{{ __('Stempel Perusahaan') }}</option>
                            </select>
                            @error('type') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control" id="company-field" style="{{ old('type', $selectedType ?? 'original') === 'company_stamp' ? '' : 'display:none;' }}">
                            <label class="label"><span class="label-text font-medium">{{ __('Perusahaan') }}</span></label>
                            <select name="company_id" class="select select-bordered w-full">
                                <option value="">{{ __('-- Pilih Perusahaan --') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ (string)old('company_id', $selectedCompanyId ?? '') === (string)$company->id ? 'selected' : '' }}>
                                        {{ $company->name }} ({{ $company->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium">{{ __('Gambar Tanda Tangan / Stempel') }}</span></label>
                            <input type="file" name="signature_image" accept="image/png,image/jpeg" class="file-input file-input-bordered w-full" required />
                            <label class="label">
                                <span class="label-text-alt text-base-content/50">{{ __('Format PNG/JPG/JPEG, maksimal 2MB. Gambar dengan background transparan disarankan.') }}</span>
                            </label>
                            @error('signature_image') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <a href="{{ route('admin.signatures.index') }}" class="btn btn-ghost btn-sm">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('signature-type').addEventListener('change', function () {
            document.getElementById('company-field').style.display = this.value === 'company_stamp' ? '' : 'none';
        });
    </script>
</x-app-layout>
