@if($document->displayVersion())
    <div class="prose max-w-none">
        {!! $document->displayVersion()->content !!}
    </div>
@else
    <p class="text-base-content/60 italic">Belum ada konten yang disetujui.</p>
@endif
