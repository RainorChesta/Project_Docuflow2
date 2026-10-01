@props([
    'type' => 'cards',
    'count' => null,
    'lines' => 3,
    'class' => '',
])

@php
    $count = $count ?? match($type) {
        'table' => 5,
        'cards' => 3,
        'list' => 4,
        'search' => 3,
        'stats' => 4,
        'summary' => 1,
        'document' => 1,
        default => 1,
    };
@endphp

<div class="w-full {{ $class }}" role="status" aria-label="{{ __('Memuat...') }}">
    {{-- ── 1. Table Shimmer Skeleton ──────────────────────────────────── --}}
    @if($type === 'table')
        <div class="bg-base-100 border border-base-300 rounded-2xl overflow-hidden shadow-xs">
            {{-- Table Header --}}
            <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-base-200 bg-base-200/40">
                <div class="shimmer h-4 w-28 rounded-md"></div>
                <div class="flex items-center gap-3">
                    <div class="shimmer h-4 w-20 rounded-md hidden sm:block"></div>
                    <div class="shimmer h-4 w-16 rounded-md"></div>
                </div>
            </div>

            {{-- Table Rows --}}
            <div class="divide-y divide-base-200/80">
                @for($i = 0; $i < $count; $i++)
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <div class="flex items-center gap-3.5 min-w-0 flex-1">
                            <div class="shimmer w-8 h-8 rounded-xl shrink-0"></div>
                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="shimmer h-3.5 rounded-md" style="width: {{ [70, 85, 60, 75, 65][$i % 5] }}%;"></div>
                                <div class="shimmer h-2.5 w-36 rounded-md opacity-70"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <div class="shimmer h-5 w-18 rounded-full hidden sm:block"></div>
                            <div class="shimmer h-4 w-14 rounded-md opacity-60"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

    {{-- ── 2. Cards Grid Shimmer Skeleton ─────────────────────────────── --}}
    @elseif($type === 'cards')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @for($i = 0; $i < $count; $i++)
                <div class="card bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="shimmer w-10 h-10 rounded-xl shrink-0"></div>
                            <div class="space-y-1.5">
                                <div class="shimmer h-4 w-28 rounded-md"></div>
                                <div class="shimmer h-2.5 w-16 rounded-md opacity-70"></div>
                            </div>
                        </div>
                        <div class="shimmer h-5 w-16 rounded-full shrink-0"></div>
                    </div>

                    {{-- Card Body Lines --}}
                    <div class="space-y-2 py-1">
                        <div class="shimmer h-3 w-full rounded-md"></div>
                        <div class="shimmer h-3 w-4/5 rounded-md"></div>
                        <div class="shimmer h-3 w-3/5 rounded-md opacity-80"></div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="flex items-center justify-between pt-2 border-t border-base-200/80">
                        <div class="flex items-center gap-2">
                            <div class="shimmer w-5 h-5 rounded-full shrink-0"></div>
                            <div class="shimmer h-2.5 w-20 rounded-md opacity-70"></div>
                        </div>
                        <div class="shimmer h-6 w-16 rounded-lg"></div>
                    </div>
                </div>
            @endfor
        </div>

    {{-- ── 3. List Items Shimmer Skeleton ────────────────────────────── --}}
    @elseif($type === 'list')
        <div class="divide-y divide-base-200/80">
            @for($i = 0; $i < $count; $i++)
                <div class="flex items-start gap-3.5 px-3.5 py-3 sm:py-3.5">
                    <div class="shimmer w-9 h-9 rounded-2xl shrink-0 mt-0.5"></div>
                    <div class="flex-1 min-w-0 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="shimmer h-3.5 rounded-md" style="width: {{ [65, 80, 55, 70][$i % 4] }}%;"></div>
                            <div class="shimmer h-2.5 w-12 rounded-md opacity-60 shrink-0"></div>
                        </div>
                        <div class="shimmer h-2.5 w-4/5 rounded-md opacity-70"></div>
                    </div>
                </div>
            @endfor
        </div>

    {{-- ── 4. Search Modal Result Shimmer Skeleton ─────────────────────── --}}
    @elseif($type === 'search')
        <div class="space-y-2 py-1">
            @for($i = 0; $i < $count; $i++)
                <div class="flex items-start gap-3 p-3 rounded-2xl bg-base-100 border border-base-200/70 shadow-2xs">
                    {{-- Visibility Avatar --}}
                    <div class="shimmer w-8 h-8 rounded-xl shrink-0 mt-0.5"></div>

                    {{-- Details --}}
                    <div class="flex-1 min-w-0 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="shimmer h-4 rounded-md" style="width: {{ [75, 60, 85, 70][$i % 4] }}%;"></div>
                            <div class="shimmer h-2.5 w-12 rounded-md opacity-50 shrink-0"></div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="shimmer h-4 w-20 rounded-md opacity-80"></div>
                            <div class="shimmer h-4 w-16 rounded-full opacity-70"></div>
                            <div class="shimmer h-4 w-24 rounded-full opacity-60"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

    {{-- ── 5. Document Page Shimmer Skeleton ───────────────────────────── --}}
    @elseif($type === 'document')
        <div class="bg-base-100 border border-base-300 rounded-2xl p-6 sm:p-10 shadow-xs space-y-8 max-w-3xl mx-auto">
            {{-- Letterhead Header --}}
            <div class="flex flex-col items-center justify-center text-center space-y-2 pb-6 border-b-2 border-base-300/80">
                <div class="shimmer w-14 h-14 rounded-2xl mb-1"></div>
                <div class="shimmer h-4 w-3/5 rounded-md"></div>
                <div class="shimmer h-3 w-2/5 rounded-md opacity-70"></div>
            </div>

            {{-- Document Title & Number --}}
            <div class="space-y-2 text-center py-2">
                <div class="shimmer h-5 w-1/2 rounded-lg mx-auto"></div>
                <div class="shimmer h-3 w-1/4 rounded-md mx-auto opacity-75"></div>
            </div>

            {{-- Paragraph 1 --}}
            <div class="space-y-2.5">
                <div class="shimmer h-3 w-full rounded-md"></div>
                <div class="shimmer h-3 w-[94%] rounded-md"></div>
                <div class="shimmer h-3 w-[97%] rounded-md"></div>
                <div class="shimmer h-3 w-[68%] rounded-md"></div>
            </div>

            {{-- Paragraph 2 --}}
            <div class="space-y-2.5">
                <div class="shimmer h-3 w-full rounded-md"></div>
                <div class="shimmer h-3 w-[90%] rounded-md"></div>
                <div class="shimmer h-3 w-[78%] rounded-md"></div>
            </div>

            {{-- Paragraph 3 --}}
            <div class="space-y-2.5">
                <div class="shimmer h-3 w-[96%] rounded-md"></div>
                <div class="shimmer h-3 w-[85%] rounded-md"></div>
                <div class="shimmer h-3 w-[45%] rounded-md"></div>
            </div>

            {{-- Bottom Signature Block --}}
            <div class="pt-8 flex justify-end">
                <div class="w-48 space-y-2.5 text-center">
                    <div class="shimmer h-3 w-28 rounded-md mx-auto"></div>
                    <div class="shimmer h-14 w-36 rounded-xl mx-auto opacity-60"></div>
                    <div class="shimmer h-3.5 w-32 rounded-md mx-auto"></div>
                    <div class="shimmer h-2.5 w-24 rounded-md mx-auto opacity-70"></div>
                </div>
            </div>
        </div>

    {{-- ── 6. AI Summary Shimmer Skeleton ──────────────────────────────── --}}
    @elseif($type === 'summary')
        <div class="bg-base-100 p-4 sm:p-5 rounded-xl border border-primary/20 shadow-xs space-y-3.5">
            {{-- Header --}}
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="shimmer w-5 h-5 rounded-md"></div>
                    <div class="shimmer h-4 w-32 rounded-md"></div>
                </div>
                <div class="shimmer h-4 w-20 rounded-full opacity-80"></div>
            </div>

            {{-- Body lines --}}
            <div class="space-y-2.5 py-1">
                <div class="shimmer h-3.5 w-full rounded-md"></div>
                <div class="shimmer h-3.5 w-[96%] rounded-md"></div>
                <div class="shimmer h-3.5 w-[92%] rounded-md"></div>
                <div class="shimmer h-3.5 w-[74%] rounded-md"></div>
            </div>

            {{-- Sub-bullets --}}
            <div class="space-y-2 pl-4 border-l-2 border-base-200">
                <div class="shimmer h-3 w-4/5 rounded-md"></div>
                <div class="shimmer h-3 w-3/5 rounded-md opacity-80"></div>
            </div>
        </div>

    {{-- ── 7. Dashboard Stats Widget Shimmer Skeleton ─────────────────── --}}
    @elseif($type === 'stats')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @for($i = 0; $i < $count; $i++)
                <div class="card bg-base-100 border border-base-300 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="shimmer h-3.5 w-24 rounded-md"></div>
                        <div class="shimmer w-9 h-9 rounded-xl"></div>
                    </div>
                    <div class="space-y-1">
                        <div class="shimmer h-7 w-20 rounded-lg"></div>
                        <div class="shimmer h-2.5 w-32 rounded-md opacity-70"></div>
                    </div>
                </div>
            @endfor
        </div>

    {{-- ── 8. Simple Text Shimmer Skeleton ─────────────────────────────── --}}
    @elseif($type === 'text')
        <div class="space-y-2.5">
            @for($i = 0; $i < $lines; $i++)
                <div class="shimmer h-3 rounded-md" style="width: {{ $i === ($lines - 1) ? '60%' : ([100, 94, 98, 88][$i % 4] . '%') }};"></div>
            @endfor
        </div>

    {{-- ── 9. Custom Slotted Shimmer ───────────────────────────────────── --}}
    @else
        <div class="space-y-3">
            {{ $slot }}
        </div>
    @endif
</div>
