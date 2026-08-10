<x-app-layout>
    <x-slot name="header">Create Document</x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label">
                                <span class="label-text font-medium">Tipe Dokumen</span>
                            </label>
                            <select name="document_type_id" id="document_type_id" class="select select-bordered w-full" required>
                                <option value="">Pilih tipe dokumen...</option>
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('document_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->code }} - {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="document_number_field" class="label">
                                <span class="label-text font-medium">Nomor Dokumen</span>
                                <span id="document-number-hint" class="label-text-alt text-base-content/50">Preview otomatis</span>
                            </label>
                            <input type="text" id="document_number_field"
                                   value="{{ old('document_number', 'Pilih tipe dokumen dahulu...') }}"
                                   class="input input-bordered w-full font-mono bg-base-200" disabled>
                            @error('document_number') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-base-content/50 mt-1">
                                Nomor final dihitung ulang saat disimpan — preview ini hanya perkiraan.
                            </p>
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label">
                                <span class="label-text font-medium">Title</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="division_id" class="label">
                                <span class="label-text font-medium">Division</span>
                            </label>
                            @if(auth()->user()->isAdmin())
                                <select name="division_id" id="division_id" class="select select-bordered w-full" required>
                                    <option value="">Pilih divisi...</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>
                                            {{ $div->code }} - {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-base-content/50 mt-1">Admin bisa pilih divisi mana pun.</p>
                            @else
                                @php($myDivision = auth()->user()->division)
                                <input type="text" value="{{ $myDivision ? $myDivision->code . ' - ' . $myDivision->name : '—' }}" class="input input-bordered w-full bg-base-200" disabled>
                                <input type="hidden" name="division_id" value="{{ auth()->user()->division_id }}">
                                <p class="text-xs text-base-content/50 mt-1">Otomatis sesuai divisi akun kamu.</p>
                            @endif
                            @error('division_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="divider"></div>

                        <div class="form-control mb-2">
                            <label class="label cursor-pointer justify-start gap-3">
                                <input type="checkbox" name="is_upload" id="is_upload" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('is_upload') ? 'checked' : '' }}>
                                <span class="label-text font-medium">Unggah dokumen yang sudah ada (bukan ditulis di editor)</span>
                            </label>
                        </div>

                        <div id="upload-field" class="form-control w-full mb-4 {{ old('is_upload') ? '' : 'hidden' }}">
                            <label for="file" class="label">
                                <span class="label-text font-medium">Berkas Dokumen</span>
                            </label>
                            <input type="file" name="file" id="file" accept=".pdf,.docx"
                                   class="file-input file-input-bordered w-full">
                            <p class="text-xs text-base-content/50 mt-1">Hanya PDF atau DOCX, maksimal 10MB.</p>
                            @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-info mt-1">
                                Setelah berkas dipilih, isi nomor dokumen di atas sesuai nomor resmi pada berkas fisik.
                            </p>
                        </div>

                        <div class="flex flex-wrap justify-end">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Create Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var typeSelect = document.getElementById('document_type_id');
            var numberField = document.getElementById('document_number_field');
            var numberHint = document.getElementById('document-number-hint');
            var uploadCheckbox = document.getElementById('is_upload');
            var uploadField = document.getElementById('upload-field');
            var fileInput = document.getElementById('file');

            var lastPreview = numberField.value;

            function fetchPreview() {
                var typeId = typeSelect.value;
                if (!typeId) {
                    numberField.value = 'Pilih tipe dokumen dahulu...';
                    lastPreview = '';
                    return;
                }

                fetch('{{ route('documents.next-number') }}?document_type_id=' + encodeURIComponent(typeId), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        lastPreview = data.number;
                        if (numberField.disabled) {
                            numberField.value = data.number;
                        }
                    })
                    .catch(function () {
                        numberField.value = 'Gagal memuat preview';
                    });
            }

            function setUploadMode(isFileChosen) {
                if (isFileChosen) {
                    numberField.disabled = false;
                    numberField.name = 'document_number';
                    numberField.classList.remove('bg-base-200');
                    numberField.value = lastPreview || '';
                    numberHint.textContent = 'Isi manual sesuai berkas';
                } else {
                    numberField.disabled = true;
                    numberField.removeAttribute('name');
                    numberField.classList.add('bg-base-200');
                    numberField.value = lastPreview || 'Pilih tipe dokumen dahulu...';
                    numberHint.textContent = 'Preview otomatis';
                }
            }

            typeSelect.addEventListener('change', fetchPreview);

            uploadCheckbox.addEventListener('change', function () {
                if (uploadCheckbox.checked) {
                    uploadField.classList.remove('hidden');
                } else {
                    uploadField.classList.add('hidden');
                    fileInput.value = '';
                    setUploadMode(false);
                }
            });

            fileInput.addEventListener('change', function () {
                setUploadMode(fileInput.files.length > 0);
            });

            // Restore state kalau form reload akibat error validasi.
            if (typeSelect.value) {
                fetchPreview();
            }
            if (uploadCheckbox.checked) {
                uploadField.classList.remove('hidden');
            }
        })();
    </script>
</x-app-layout>
