<form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex flex-col sm:flex-row flex-wrap gap-2 items-stretch sm:items-center w-full">
    <input type="hidden" name="type" value="{{ $type ?? request('type', 'general') }}">
    @if(isset($folder))
        <input type="hidden" name="folder" value="{{ $folder }}">
    @endif
    
    <div class="flex-1 min-w-[180px]">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari judul atau nomor...') }}"
               class="input input-bordered input-sm w-full">
    </div>
    
    <x-document-type-filter :documentTypes="$documentTypes" :selected="request('document_type_id')" placeholder="{{ __('Semua Tipe') }}" />
    
    @if(isset($divisions))
    <select name="division_id" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Divisi') }}</option>
        @foreach($divisions as $div)
            <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                {{ $div->name }} ({{ strtoupper($div->code) }})
            </option>
        @endforeach
    </select>
    @endif
    
    @if(isset($branches))
    <select name="filter_branch_id" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Cabang') }}</option>
        @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ request('filter_branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }}
            </option>
        @endforeach
    </select>
    @endif
    
    <select name="format_choice" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Format') }}</option>
        <option value="baru" {{ request('format_choice') == 'baru' ? 'selected' : '' }}>{{ __('Format Baru') }}</option>
        <option value="lama" {{ request('format_choice') == 'lama' ? 'selected' : '' }}>{{ __('Format Lama') }}</option>
    </select>
    
    <select name="year" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Tahun') }}</option>
        @for($y = date('Y'); $y >= 2020; $y--)
            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    
    <select name="per_page" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10 / hal</option>
        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 / hal</option>
        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25 / hal</option>
        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50 / hal</option>
        <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100 / hal</option>
    </select>
    
    <div class="flex flex-wrap gap-2 items-center sm:ml-auto shrink-0 w-full sm:w-auto justify-between sm:justify-end mt-1 sm:mt-0">
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-outline btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                {{ __('Cari') }}
            </button>
            @if(request('search') || request('status') || request('division_id') || request('document_type_id') || request('filter_branch_id') || request('year') || request('format_choice'))
                <a href="{{ route('documents.index', ['type' => $type, 'folder' => request('folder')]) }}" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ __('Bersihkan') }}
                </a>
            @endif
        </div>
        
        <div class="join">
            <button type="button" @click="viewMode = 'list'" class="btn btn-sm join-item" :class="viewMode === 'list' ? 'btn-active' : 'btn-ghost'" title="{{ __('Tampilan List') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
            <button type="button" @click="viewMode = 'grid'" class="btn btn-sm join-item" :class="viewMode === 'grid' ? 'btn-active' : 'btn-ghost'" title="{{ __('Tampilan Grid') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            </button>
        </div>
    </div>
</form>
