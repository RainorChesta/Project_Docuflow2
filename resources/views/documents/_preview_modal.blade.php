<div
    x-data="previewModal()"
    x-init="$watch('open', value => { document.body.classList.toggle('overflow-hidden', value) })"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-start justify-center px-4 py-6 sm:px-0"
>
    <div class="fixed inset-0 bg-base-content/40 backdrop-blur-sm" x-on:click="open = false"></div>

    <div class="relative bg-base-100 rounded-box shadow-lg border border-base-300 w-full max-w-4xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-4 border-b border-base-300 shrink-0">
            <div class="min-w-0 flex-1">
                <div class="font-semibold text-base-content break-words" x-text="title"></div>
                <div class="text-sm text-base-content/60 break-words" x-text="subtitle"></div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-circle shrink-0" x-on:click="open = false">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto px-4 sm:px-6 py-4" x-show="!loading">
            <div x-html="content"></div>
        </div>
        <div class="px-4 sm:px-6 py-8 text-center text-base-content/60" x-show="loading">Memuat preview...</div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('previewModal', () => ({
            open: false,
            loading: false,
            content: '',
            title: '',
            subtitle: '',
            async previewDoc(url, docTitle, docSubtitle) {
                this.title = docTitle;
                this.subtitle = docSubtitle;
                this.open = true;
                this.loading = true;
                this.content = '';
                try {
                    const res = await fetch(url);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.content = await res.text();
                } catch (e) {
                    this.content = '<p class="text-error">Gagal memuat preview.</p>';
                } finally {
                    this.loading = false;
                }
            },
        }));
    });
</script>
