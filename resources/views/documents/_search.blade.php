<form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex flex-col sm:flex-row flex-wrap gap-2">
    <input type="hidden" name="type" value="{{ $type ?? request('type', 'general') }}">
    @if(isset($folder))
        <input type="hidden" name="folder" value="{{ $folder }}">
    @endif
    
    <div class="flex-1 min-w-[200px]">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari judul atau nomor...') }}"
               class="input input-bordered input-sm w-full">
    </div>
    
    <select name="document_type_id" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Tipe') }}</option>
        @foreach($documentTypes as $dt)
            <option value="{{ $dt->id }}" {{ request('document_type_id') == $dt->id ? 'selected' : '' }}>
                {{ $dt->code }}
            </option>
        @endforeach
    </select>
    
    @if(isset($divisions))
    <select name="division_id" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Divisi') }}</option>
        @foreach($divisions as $div)
            <option value="{{ $div->id }}" {{ request('division_id') == $div->id ? 'selected' : '' }}>
                {{ $div->code }}
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
    
    <select name="year" class="select select-bordered select-sm w-full sm:w-auto" onchange="this.form.submit()">
        <option value="">{{ __('Semua Tahun') }}</option>
        @for($y = date('Y'); $y >= 2020; $y--)
            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
    
    <button type="submit" class="btn btn-outline btn-primary btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        {{ __('Cari') }}
    </button>
    @if(request('search') || request('status') || request('division_id') || request('document_type_id') || request('filter_branch_id') || request('year'))
        <a href="{{ route('documents.index', ['type' => $type, 'folder' => request('folder')]) }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            {{ __('Bersihkan') }}
        </a>
    @endif
    
    <div class="divider divider-horizontal hidden sm:flex m-0"></div>
    
    <div class="join">
        <button type="button" @click="viewMode = 'list'" class="btn btn-sm join-item" :class="viewMode === 'list' ? 'btn-active' : 'btn-ghost'">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <button type="button" @click="viewMode = 'grid'" class="btn btn-sm join-item" :class="viewMode === 'grid' ? 'btn-active' : 'btn-ghost'">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
        </button>
    </div>
</form>
