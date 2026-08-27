@props(['isDirty' => 'false'])

<div x-data="navigationGuardComponent({ isDirty: {{ $isDirty }} })" x-init="initGuard()">
    <x-modal name="confirm-navigation-modal" :show="false" maxWidth="sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-base-content">{{ __('Leave this page?') }}</h3>
            <p class="mt-2 text-sm text-base-content/70">
                {{ __('You have unsaved changes. Are you sure you want to leave this page? Your changes may not be saved.') }}
            </p>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="btn btn-ghost" x-on:click="cancelLeave()">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-warning" x-on:click="confirmLeave()">
                    {{ __('Leave') }}
                </button>
            </div>
        </div>
    </x-modal>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('navigationGuardComponent', (config) => ({
            isDirty: config.isDirty,
            pendingUrl: null,
            isIntentionalLeave: false,
            initGuard() {
                // Aggressive capture-phase beforeunload to silence third-party scripts (like ONLYOFFICE)
                // from showing the native browser prompt when we already confirmed via custom modal.
                window.addEventListener('beforeunload', (e) => {
                    if (this.isIntentionalLeave) {
                        e.stopImmediatePropagation();
                    }
                }, true);

                // Removed native beforeunload as requested

                // 3 & 4: Intercept popstate (Back/Forward buttons)
                history.pushState({ navigationGuard: true }, "");
                window.addEventListener('popstate', (e) => {
                    if (!this.isDirty || this.isIntentionalLeave) return;

                    // Trap them in the current state
                    history.pushState({ navigationGuard: true }, "");
                    
                    this.pendingUrl = 'history_back';
                    this.$dispatch('open-modal', 'confirm-navigation-modal');
                });

                // 1 & 2: Intercept internal navigation links
                window.addEventListener('click', (e) => {
                    if (!this.isDirty || this.isIntentionalLeave) return;
                    
                    const link = e.target.closest('a[href]');
                    if (!link) return;
                    
                    const href = link.getAttribute('href');
                    
                    // Ignore non-navigation links
                    if (href.startsWith('#') || href.startsWith('javascript:') || 
                        link.hasAttribute('download') || link.getAttribute('target') === '_blank' || 
                        href.includes('/download')) {
                        return;
                    }

                    // Prevent navigation
                    e.preventDefault();
                    e.stopPropagation();

                    this.pendingUrl = href;
                    this.$dispatch('open-modal', 'confirm-navigation-modal');
                }, true); // Capturing phase to beat other libraries
                
                // Intercept generic form submissions to prevent native prompt if we want,
                // But typically if they submit a form, it IS intentional leave.
                window.addEventListener('submit', (e) => {
                    // Let form submits proceed without the beforeunload native warning
                    this.isIntentionalLeave = true;
                });

                // Global API for other scripts
                window.setNavigationDirty = (state) => {
                    this.isDirty = state;
                };
                
                window.allowIntentionalLeave = () => {
                    this.isIntentionalLeave = true;
                };
            },

            cancelLeave() {
                this.pendingUrl = null;
                this.$dispatch('close-modal', 'confirm-navigation-modal');
            },

            confirmLeave() {
                this.isIntentionalLeave = true;
                this.$dispatch('close-modal', 'confirm-navigation-modal');
                
                // Forcefully NUKE all iframes from the DOM.
                // Since ONLYOFFICE runs in an iframe on a different port (cross-origin), 
                // its internal beforeunload listener triggers the browser warning.
                // Removing the iframe instantly destroys its window and bypasses the warning!
                document.querySelectorAll('iframe').forEach(iframe => iframe.remove());
                
                const container = document.getElementById('onlyoffice-editor-container');
                if (container) container.remove();

                if (window.docEditor) {
                    try { window.docEditor.destroyEditor(); } catch (e) {}
                }
                
                // Continue the intended navigation
                if (this.pendingUrl === 'history_back') {
                    history.go(-2);
                } else if (this.pendingUrl) {
                    window.location.href = this.pendingUrl;
                }
            }
        }));
    });
</script>
@endpush
