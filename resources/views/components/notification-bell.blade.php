{{-- Notification Bell — top-right icon with dropdown panel.
     Uses Alpine.js for state. Polls via fetch for unread count,
     and optionally listens via Echo when Reverb is available. --}}
<div x-data="{
        open: false,
        notifications: [],
        unreadCount: 0,
        loading: false,
        isFetching: false,
        init() {
            this.fetchUnreadCount();
            // Fallback poll every 15s (toast component also broadcasts updates)
            setInterval(() => this.fetchUnreadCount(), 15000);

            // Sync immediately with toast component polling
            window.addEventListener('notifications-updated', (event) => {
                if (event.detail) {
                    if (typeof event.detail.unread_count !== 'undefined') {
                        this.unreadCount = event.detail.unread_count;
                    }
                    if (event.detail.notifications) {
                        this.notifications = event.detail.notifications.map(n => ({
                            ...n,
                            expanded: false
                        }));
                        this.loading = false;
                    }
                }
            });

            // Re-fetch on focus / visibility change
            window.addEventListener('focus', () => this.fetchUnreadCount());
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.fetchUnreadCount();
            });

            // If Echo is available (Reverb running), listen for real-time updates
            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification((notification) => {
                        this.unreadCount++;
                        if (this.open) {
                            this.fetchNotifications(false);
                        }
                        window.dispatchEvent(new CustomEvent('notification-received'));
                    });

                window.Echo.private('notifications.{{ auth()->id() }}')
                    .listen('.notification.new', (data) => {
                        this.unreadCount++;
                        if (this.open) {
                            this.fetchNotifications(false);
                        }
                        window.dispatchEvent(new CustomEvent('notification-received'));
                    });
            }
        },
        fetchUnreadCount() {
            if (this.isFetching) return;
            fetch('{{ route('notifications.unread-count') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { 
                this.unreadCount = data.unread_count; 
            })
            .catch(() => {});
        },
        fetchNotifications(showSpinner = true) {
            if (this.isFetching) return;
            if (showSpinner && this.notifications.length === 0) {
                this.loading = true;
            }
            this.isFetching = true;

            fetch('{{ route('notifications.index') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.notifications = (data.notifications || []).map(n => ({
                    ...n,
                    expanded: false
                }));
                this.unreadCount = data.unread_count;
            })
            .catch(() => {})
            .finally(() => {
                this.loading = false;
                this.isFetching = false;
            });
        },
        formatUrl(url) {
            if (!url || url === '#') return '#';
            if (url.includes('host.docker.internal')) {
                try {
                    const u = new URL(url);
                    return u.pathname + u.search + u.hash;
                } catch (e) {
                    return url.replace(/^https?:\/\/host\.docker\.internal(:\d+)?/, '') || '#';
                }
            }
            return url;
        },
        togglePanel() {
            this.open = !this.open;
            if (this.open) {
                const showSpinner = this.notifications.length === 0;
                this.fetchNotifications(showSpinner);
            }
        },
        markAsRead(id) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                }
            })
            .then(r => r.json())
            .then(data => { 
                this.unreadCount = data.unread_count; 
                this.notifications = this.notifications.map(n => n.id === id ? {...n, read: true} : n);
                window.dispatchEvent(new CustomEvent('notifications-read', { detail: data }));
            })
            .catch(() => {});
        },
        markAllAsRead() {
            fetch('{{ route('notifications.read-all') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                }
            })
            .then(r => r.json())
            .then(data => {
                this.unreadCount = 0;
                this.notifications = this.notifications.map(n => ({...n, read: true}));
                window.dispatchEvent(new CustomEvent('notifications-read', { detail: { unread_count: 0 } }));
            })
            .catch(() => {});
        },
        iconForType(type) {
            const icons = {
                signature: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' />`,
                stamp: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />`,
                document: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />`,
                approval: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                rejected: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                bell: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' />`,
            };
            return icons[type] || icons.bell;
        }
     }"
     @click.outside="open = false"
     class="relative">

    {{-- Bell button --}}
    <button type="button"
            class="btn btn-ghost btn-sm btn-square relative rounded-xl"
            title="{{ __('Notifikasi') }}"
            aria-label="{{ __('Notifikasi') }}"
            @click="togglePanel()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Badge --}}
        <span x-show="unreadCount > 0"
              x-transition
              class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-error rounded-full ring-2 ring-base-100 shadow-xs"
              x-text="unreadCount > 99 ? '99+' : unreadCount">
        </span>
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         class="absolute right-0 top-full mt-2 w-[calc(100vw-1.5rem)] sm:w-[440px] md:w-[480px] bg-base-100 border border-base-300/90 rounded-3xl shadow-2xl overflow-hidden z-50"
         x-cloak>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 sm:px-5 py-3.5 border-b border-base-200 bg-base-200/40">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-base-content">{{ __('Notifikasi') }}</h3>
                <template x-if="unreadCount > 0">
                    <span class="badge badge-primary badge-xs font-bold px-2 py-0.5" x-text="`${unreadCount} baru`"></span>
                </template>
            </div>
            <button type="button"
                    class="text-xs text-primary hover:text-primary/80 font-semibold transition-colors"
                    @click="markAllAsRead()"
                    x-show="unreadCount > 0">
                {{ __('Tandai semua dibaca') }}
            </button>
        </div>

        {{-- Notification list --}}
        <div class="max-h-[460px] overflow-y-auto">
            {{-- Loading state --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-10">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
            </template>

            {{-- Notifications --}}
            <template x-if="!loading && notifications.length > 0">
                <ul class="divide-y divide-base-200/80">
                    <template x-for="notif in notifications" :key="notif.id">
                        <li>
                            <a :href="formatUrl(notif.url)"
                               class="flex items-start gap-3.5 px-4 sm:px-5 py-4 transition-colors border-l-4 group"
                               :class="((notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked'))
                                   ? (notif.read ? 'border-l-error/30 hover:bg-base-200/60 bg-base-100' : 'border-l-error bg-error/5 hover:bg-error/10')
                                   : ((notif.status === 'approved' || (notif.type || '').includes('approved'))
                                       ? (notif.read ? 'border-l-success/30 hover:bg-base-200/60 bg-base-100' : 'border-l-success bg-success/5 hover:bg-success/10')
                                       : (notif.read ? 'border-l-transparent hover:bg-base-200/60 bg-base-100' : 'border-l-primary bg-primary/5 hover:bg-primary/10')))"
                               @click="markAsRead(notif.id)">
                                {{-- Icon --}}
                                <div class="mt-0.5 shrink-0 h-9 w-9 flex items-center justify-center rounded-2xl ring-2 shadow-xs transition-transform group-hover:scale-105"
                                     :class="notif.read 
                                        ? 'bg-base-200 text-base-content/40 ring-base-300/30' 
                                        : ((notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked')) 
                                            ? 'bg-error/15 text-error ring-error/20' 
                                            : ((notif.status === 'approved' || (notif.type || '').includes('approved')) 
                                                ? 'bg-success/15 text-success ring-success/20' 
                                                : 'bg-primary/15 text-primary ring-primary/20'))">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         x-html="iconForType(notif.icon)">
                                    </svg>
                                </div>

                                {{-- Main Content --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Header: Status/Title & Time --}}
                                    <div class="flex items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                                            <span class="text-xs font-bold leading-snug truncate"
                                                  :class="notif.read
                                                     ? ((notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked')) ? 'text-error/70' : 'text-base-content/70')
                                                     : ((notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked')) ? 'text-error font-bold' : 'text-base-content font-bold')"
                                                  x-text="notif.title"></span>
                                            
                                            {{-- Status Badges --}}
                                            <template x-if="notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked')">
                                                <span class="badge badge-error badge-xs font-bold text-white uppercase shrink-0">{{ __('Ditolak') }}</span>
                                            </template>
                                            <template x-if="notif.status === 'approved' || (notif.type || '').includes('approved')">
                                                <span class="badge badge-success badge-xs font-bold text-white uppercase shrink-0">{{ __('Disetujui') }}</span>
                                            </template>
                                            <template x-if="(notif.type || '').includes('stamp_request')">
                                                <span class="badge badge-secondary badge-xs font-bold uppercase shrink-0">{{ __('Stempel') }}</span>
                                            </template>
                                            <template x-if="(notif.type || '').includes('signature_request') && !(notif.type || '').includes('stamp') && !(notif.type || '').includes('approved') && !(notif.type || '').includes('rejected')">
                                                <span class="badge badge-primary badge-xs font-bold uppercase shrink-0">{{ __('TTD') }}</span>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="text-[10px] text-base-content/45 font-medium whitespace-nowrap" x-text="notif.time"></span>
                                            <span x-show="!notif.read" class="block h-2 w-2 rounded-full ring-2 ring-base-100" 
                                                  :class="(notif.status === 'rejected' || notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked')) ? 'bg-error' : ((notif.status === 'approved' || (notif.type || '').includes('approved')) ? 'bg-success' : 'bg-primary')"></span>
                                        </div>
                                    </div>

                                    {{-- Message body --}}
                                    <div class="text-xs text-base-content/75 leading-relaxed break-words [overflow-wrap:anywhere]" 
                                         :class="notif.expanded ? '' : 'line-clamp-3'" 
                                         x-text="notif.message"></div>
                                    
                                    {{-- Expand toggle for long message --}}
                                    <template x-if="notif.message && notif.message.length > 90">
                                        <button type="button" 
                                                @click.stop.prevent="notif.expanded = !notif.expanded" 
                                                class="mt-1 text-[11px] font-semibold text-primary hover:underline flex items-center gap-1">
                                            <span x-text="notif.expanded ? '{{ __('Sembunyikan detail') }}' : '{{ __('Selengkapnya') }}'"></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="notif.expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </template>

                                    {{-- Dedicated Document Box --}}
                                    <template x-if="notif.document_title">
                                        <div class="mt-2 p-2.5 rounded-xl bg-base-200/70 dark:bg-base-300/40 border border-base-300/70 flex items-start gap-2.5 min-w-0 max-w-full group-hover:border-primary/40 group-hover:bg-base-200/90 transition-colors">
                                            <div class="p-1.5 rounded-lg bg-base-100 dark:bg-base-200 text-primary shrink-0 mt-0.5 shadow-2xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[11px] font-semibold text-base-content break-words [overflow-wrap:anywhere] leading-snug" x-text="notif.document_title"></div>
                                                <template x-if="notif.document_number">
                                                    <div class="text-[10px] text-base-content/50 font-mono tracking-tight mt-0.5 truncate" x-text="notif.document_number"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Reason Callout Block --}}
                                    <template x-if="notif.reason">
                                        <div class="mt-2 p-2.5 rounded-xl bg-error/10 border border-error/20 text-xs transition-all min-w-0 max-w-full overflow-hidden">
                                            <div class="flex items-center gap-1.5 text-error font-bold text-[11px] mb-1 uppercase tracking-wider">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                <span>{{ __('Alasan Penolakan') }}:</span>
                                            </div>
                                            <div class="text-base-content/85 dark:text-base-content/90 font-normal leading-relaxed break-words [overflow-wrap:anywhere] whitespace-pre-wrap text-[11px] max-h-32 overflow-y-auto pr-1"
                                                 x-text="notif.reason"></div>
                                        </div>
                                    </template>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            {{-- Empty state --}}
            <template x-if="!loading && notifications.length === 0">
                <div class="flex flex-col items-center justify-center py-12 text-base-content/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="text-sm font-medium">{{ __('Belum ada notifikasi') }}</span>
                </div>
            </template>
        </div>
    </div>
</div>
