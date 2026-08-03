@if($document->displayVersion())
    @include('documents._paper', ['content' => $document->displayVersion()->content])
@else
    <p class="text-base-content/60 italic">Belum ada konten yang disetujui.</p>
@endif
