{{-- Notification Bell — top-right icon with dropdown panel.
     Uses Alpine.js for state. Polls via fetch for unread count,
     and optionally listens via Echo when Reverb is available. --}}
<div x-data="{
        open: false,
        notifications: [],
        unreadCount: 0,
        loading: false,
        init() {
            this.fetchUnreadCount();
            // Poll every 30s as fallback (works even without Reverb)
            setInterval(() => this.fetchUnreadCount(), 30000);

            // If Echo is available (Reverb running), listen for real-time updates
            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification((notification) => {
                        this.unreadCount++;
                        if (this.open) {
                            this.fetchNotifications();
                        }
                    });

                window.Echo.private('notifications.{{ auth()->id() }}')
                    .listen('.notification.new', (data) => {
                        this.unreadCount++;
                        if (this.open) {
                            this.fetchNotifications();
                        }
                    });
            }
        },
        fetchUnreadCount() {
            fetch('{{ route('notifications.unread-count') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.unreadCount = data.unread_count; })
            .catch(() => {});
        },
        fetchNotifications() {
            this.loading = true;
            fetch('{{ route('notifications.index') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.loading = false;
            })
            .catch(() => { this.loading = false; });
        },
        togglePanel() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications();
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
            .then(data => { this.unreadCount = data.unread_count; })
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
            })
            .catch(() => {});
        },
        iconForType(type) {
            const icons = {
                signature: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' />`,
                document: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />`,
                approval: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                bell: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' />`,
            };
            return icons[type] || icons.bell;
        }
     }"
     @click.outside="open = false"
     class="relative">

    {{-- Bell button --}}
    <button type="button"
            class="btn btn-ghost btn-sm btn-square relative"
            title="{{ __('Notifikasi') }}"
            aria-label="{{ __('Notifikasi') }}"
            @click="togglePanel()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Badge --}}
        <span x-show="unreadCount > 0"
              x-transition
              class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-error rounded-full"
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
         class="absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-96 bg-base-100 border border-base-300 rounded-xl shadow-2xl overflow-hidden z-50"
         x-cloak>

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-base-300">
            <h3 class="text-sm font-semibold text-base-content">{{ __('Notifikasi') }}</h3>
            <button type="button"
                    class="text-xs text-primary hover:text-primary/80 transition-colors"
                    @click="markAllAsRead()"
                    x-show="unreadCount > 0">
                {{ __('Tandai semua dibaca') }}
            </button>
        </div>

        {{-- Notification list --}}
        <div class="max-h-80 overflow-y-auto">
            {{-- Loading state --}}
            <template x-if="loading">
                <div class="flex items-center justify-center py-8">
                    <span class="loading loading-spinner loading-sm text-primary"></span>
                </div>
            </template>

            {{-- Notifications --}}
            <template x-if="!loading && notifications.length > 0">
                <ul class="divide-y divide-base-300/50">
                    <template x-for="notif in notifications" :key="notif.id">
                        <li>
                            <a :href="notif.url"
                               class="flex items-start gap-3 px-4 py-3 transition-colors"
                               :class="notif.read ? 'hover:bg-base-200' : 'bg-primary/5 hover:bg-primary/10'"
                               @click="markAsRead(notif.id)">
                                {{-- Icon --}}
                                <div class="mt-0.5 shrink-0 h-8 w-8 flex items-center justify-center rounded-full"
                                     :class="notif.read ? 'bg-base-200 text-base-content/40' : 'bg-primary/15 text-primary'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         x-html="iconForType(notif.icon)">
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-base-content" :class="notif.read ? 'opacity-60' : ''" x-text="notif.title"></div>
                                    <div class="text-xs text-base-content/50 mt-0.5 line-clamp-2" x-text="notif.message"></div>
                                    <div class="text-[10px] text-base-content/30 mt-1" x-text="notif.time"></div>
                                </div>
                                {{-- Unread dot --}}
                                <div x-show="!notif.read" class="mt-2 shrink-0">
                                    <span class="block h-2 w-2 rounded-full bg-primary"></span>
                                </div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>

            {{-- Empty state --}}
            <template x-if="!loading && notifications.length === 0">
                <div class="flex flex-col items-center justify-center py-10 text-base-content/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="text-sm">{{ __('Belum ada notifikasi') }}</span>
                </div>
            </template>
        </div>
    </div>
</div>
