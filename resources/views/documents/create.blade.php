<x-app-layout>
    <x-slot name="header">{{ __('Buat Dokumen') }}</x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label">
                                <span class="label-text font-medium">{{ __('Tipe Dokumen') }}</span>
                            </label>
                            <div x-data="{
                                    search: '',
                                    open: false,
                                    selectedId: '{{ old('document_type_id') }}',
                                    options: [
                                        @foreach($documentTypes as $type)
                                            { id: '{{ $type->id }}', label: '{{ $type->code }} - {{ $type->name }}' },
                                        @endforeach
                                    ],
                                    get filteredOptions() {
                                        if (this.search === '') return this.options;
                                        const searchLower = this.search.toLowerCase();
                                        return this.options.filter(opt => opt.label.toLowerCase().includes(searchLower));
                                    },
                                    get selectedLabel() {
                                        let selected = this.options.find(opt => opt.id == this.selectedId);
                                        return selected ? selected.label : '{{ __('Pilih tipe dokumen...') }}';
                                    },
                                    selectOption(id) {
                                        this.selectedId = id;
                                        this.open = false;
                                        this.search = '';
                                        $nextTick(() => {
                                            document.getElementById('document_type_id').dispatchEvent(new Event('change'));
                                        });
                                    }
                                }"
                                class="relative w-full"
                                @click.away="open = false"
                            >
                                <!-- Hidden input for form submission and JS events -->
                                <input type="hidden" name="document_type_id" id="document_type_id" :value="selectedId">
                            
                                <!-- Trigger button -->
                                <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" class="select select-bordered w-full flex items-center justify-between font-normal" :class="{'!outline-none !ring-2 !ring-primary/20 !border-primary': open}">
                                    <span x-text="selectedLabel" :class="{'text-base-content/50': !selectedId}"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            
                                <!-- Dropdown menu -->
                                <div x-show="open" style="display: none;" class="absolute z-10 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-lg">
                                    <div class="p-2 border-b border-base-300">
                                        <input x-ref="searchInput" type="text" x-model="search" class="input input-sm input-bordered w-full" placeholder="{{ __('Cari tipe dokumen...') }}" @keydown.enter.prevent="">
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto p-1">
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <li>
                                                <button type="button" @click="selectOption(option.id)" class="w-full text-left px-3 py-2 rounded-lg hover:bg-base-200 transition-colors" :class="{'bg-base-200 font-medium': selectedId == option.id}">
                                                    <span x-text="option.label"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0" class="text-center py-3 text-base-content/50 text-sm">
                                            {{ __('Tipe dokumen tidak ditemukan.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="document_number_field" class="label">
                                <span class="label-text font-medium">{{ __('Nomor Dokumen') }}</span>
                                <span id="document-number-hint" class="label-text-alt text-base-content/50">{{ __('Preview otomatis') }}</span>
                            </label>
                            <input type="text" id="document_number_field"
                                   value="{{ old('document_number', __('Pilih tipe dokumen dahulu...')) }}"
                                   class="input input-bordered w-full font-mono bg-base-200" disabled>
                            @error('document_number') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-base-content/50 mt-1">
                                {{ __('Nomor final dihitung ulang saat disimpan — preview ini hanya perkiraan.') }}
                            </p>
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label">
                                <span class="label-text font-medium">{{ __('Judul') }}</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="division_id" class="label">
                                <span class="label-text font-medium">{{ __('Divisi') }}</span>
                            </label>
                            @if(auth()->user()->isAdmin())
                                <select name="division_id" id="division_id" class="select select-bordered w-full" required>
                                    <option value="">{{ __('Pilih divisi...') }}</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>
                                            {{ $div->code }} - {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Admin bisa pilih divisi mana pun.') }}</p>
                            @else
                                @php($myDivision = auth()->user()->division)
                                <input type="text" value="{{ $myDivision ? $myDivision->code . ' - ' . $myDivision->name : '—' }}" class="input input-bordered w-full bg-base-200" disabled>
                                <input type="hidden" name="division_id" value="{{ auth()->user()->division_id }}">
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Otomatis sesuai divisi akun kamu.') }}</p>
                            @endif
                            @error('division_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="divider"></div>

                        <div class="form-control mb-2">
                            <label class="label cursor-pointer justify-start gap-3">
                                <input type="checkbox" name="is_upload" id="is_upload" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('is_upload') ? 'checked' : '' }}>
                                <span class="label-text font-medium">{{ __('Unggah dokumen yang sudah ada (bukan ditulis di editor)') }}</span>
                            </label>
                        </div>

                        <div id="upload-field" class="form-control w-full mb-4 {{ old('is_upload') ? '' : 'hidden' }}">
                            <label for="file" class="label">
                                <span class="label-text font-medium">{{ __('Berkas Dokumen') }}</span>
                            </label>
                            <input type="file" name="file" id="file" accept=".pdf,.docx"
                                   class="file-input file-input-bordered w-full">
                            <p class="text-xs text-base-content/50 mt-1">{{ __('Hanya PDF atau DOCX, maksimal 10MB.') }}</p>
                            @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-info mt-1">
                                {{ __('Setelah berkas dipilih, isi nomor dokumen di atas sesuai nomor resmi pada berkas fisik.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap justify-end">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ __('Buat Dokumen') }}
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
