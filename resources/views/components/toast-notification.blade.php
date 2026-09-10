@if (session('urgent_expiring_count'))
    <div x-data="{ 
            show: true,
            totalDuration: 8000,
            remainingTime: 8000,
            timer: null,
            init() {
                const stepMs = 50;
                this.timer = setInterval(() => {
                    this.remainingTime -= stepMs;
                    if (this.remainingTime <= 0) {
                        this.show = false;
                        clearInterval(this.timer);
                    }
                }, stepMs);
            }
         }" 
         x-show="show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8 sm:translate-x-0 sm:translate-y-4 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-x-0 sm:translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 sm:scale-100 translate-x-0"
         x-transition:leave-end="opacity-0 sm:scale-95 translate-x-8"
         class="toast toast-top toast-end z-[100] mt-16 sm:mt-2 mr-2 pointer-events-none">
        
        <div class="pointer-events-auto bg-base-100/95 dark:bg-base-100/90 backdrop-blur-md border border-warning/40 text-base-content shadow-2xl flex flex-row items-start gap-3 w-80 sm:w-96 rounded-2xl relative overflow-hidden p-3.5 pb-4">
            <!-- Decorative left border -->
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-warning"></div>
            
            <div class="w-8 h-8 rounded-xl bg-warning/10 text-warning flex items-center justify-center shrink-0 ring-2 ring-warning/20 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <div class="flex-1 min-w-0 py-0.5">
                <h3 class="font-bold text-xs text-base-content">{{ __('Perhatian: Dokumen Mendesak') }}</h3>
                <p class="text-xs text-base-content/70 mt-0.5 leading-relaxed">
                    {{ __('Terdapat :count dokumen yang akan kedaluwarsa dalam 3 hari ke depan.', ['count' => session('urgent_expiring_count')]) }}
                </p>
                <div class="mt-2">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-warning hover:text-warning/80 transition-colors inline-flex items-center gap-1 hover:underline">
                        {{ __('Lihat Daftar Dokumen') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
            
            <button @click="show = false; clearInterval(timer)" class="btn btn-ghost btn-xs btn-circle h-6 w-6 min-h-0 text-base-content/40 hover:text-base-content hover:bg-base-200 transition-colors" aria-label="{{ __('Tutup') }}">
                ✕
            </button>

            <!-- Bottom Countdown Loading Bar -->
            <div class="w-full h-0.5 bg-warning/20 absolute bottom-0 left-0 right-0 overflow-hidden">
                <div class="h-full bg-warning transition-all duration-75 ease-linear"
                     :style="`width: ${Math.max(0, Math.min(100, (remainingTime / totalDuration) * 100))}%;`">
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Global Notification Toast (shows once per notification on any page) --}}
@auth
<div x-data="{
        toasts: [],
        totalDuration: 8000,
        remainingTime: 8000,
        timerInterval: null,
        isPaused: false,
        isFetching: false,
        pollInterval: null,
        init() {
            this.checkNotifications();
            // Poll every 5 seconds for real-time notifications fallback (instant if Echo active)
            this.pollInterval = setInterval(() => this.checkNotifications(), 5000);

            // Immediately check when user switches to or focuses this window/tab
            window.addEventListener('focus', () => this.checkNotifications());
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.checkNotifications();
            });

            if (typeof window.Echo !== 'undefined') {
                window.Echo.private('App.Models.User.{{ auth()->id() }}')
                    .notification(() => {
                        this.checkNotifications();
                    });

                window.Echo.private('notifications.{{ auth()->id() }}')
                    .listen('.notification.new', () => {
                        this.checkNotifications();
                    });
            }

            window.addEventListener('notification-received', () => this.checkNotifications());
            window.addEventListener('notifications-read', () => this.checkNotifications());
        },
        checkNotifications() {
            if (this.isFetching) return;
            this.isFetching = true;

            fetch('{{ route('notifications.index') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                // Sync with notification bell immediately
                window.dispatchEvent(new CustomEvent('notifications-updated', { detail: data }));

                const storageKey = 'general_toasts_shown_{{ auth()->id() }}';
                const shown = JSON.parse(localStorage.getItem(storageKey) || '[]');
                const newNotifs = (data.notifications || []).filter(n => 
                    !n.read && !shown.includes(n.id)
                );

                if (newNotifs.length > 0) {
                    // Mark all new unread notifications as shown in localStorage so they don't spam on repeated polls
                    newNotifs.forEach(n => {
                        if (!shown.includes(n.id)) {
                            shown.push(n.id);
                        }
                    });
                    localStorage.setItem(storageKey, JSON.stringify(shown.slice(-150)));

                    const currentToastIds = this.toasts.map(t => t.id);
                    const unshownNotifs = newNotifs.filter(n => !currentToastIds.includes(n.id));

                    // Only toast up to 2 items at a time to prevent screen flooding
                    const incomingToasts = unshownNotifs.slice(0, 2).map(notif => {
                        return {
                            id: notif.id,
                            title: notif.title,
                            message: notif.message,
                            reason: notif.reason,
                            document_title: notif.document_title,
                            document_number: notif.document_number,
                            actor_name: notif.actor_name,
                            type: notif.type,
                            icon: notif.icon,
                            status: notif.status,
                            url: this.formatUrl(notif.url),
                            time: notif.time,
                            isRejected: notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked'),
                            isApproved: (notif.type || '').includes('approved') || notif.icon === 'approval' || notif.status === 'approved',
                            expanded: false
                        };
                    });

                    if (incomingToasts.length > 0) {
                        this.toasts = [...incomingToasts, ...this.toasts].slice(0, 2);
                        this.startDismissTimer();
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
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
        startDismissTimer() {
            this.stopDismissTimer();
            this.remainingTime = this.totalDuration;
            const stepMs = 50;
            this.timerInterval = setInterval(() => {
                if (!this.isPaused && !this.toasts.some(t => t.expanded)) {
                    this.remainingTime -= stepMs;
                    if (this.remainingTime <= 0) {
                        this.toasts = [];
                        this.stopDismissTimer();
                    }
                }
            }, stepMs);
        },
        stopDismissTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },
        pauseTimer() {
            this.isPaused = true;
        },
        resumeTimer() {
            this.isPaused = false;
        },
        dismiss(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
            if (this.toasts.length === 0) {
                this.stopDismissTimer();
            }
        },
        dismissAll() {
            this.toasts = [];
            this.stopDismissTimer();
        },
        markAsRead(id) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                }
            }).then(r => r.json()).then(data => {
                window.dispatchEvent(new CustomEvent('notifications-read', { detail: data }));
            }).catch(() => {});
        }
     }"
     @mouseenter="pauseTimer()"
     @mouseleave="resumeTimer()"
     class="fixed top-16 right-3 sm:right-5 z-[100] flex flex-col gap-2.5 pointer-events-none max-w-full">
    
    {{-- Dismiss All Pill when multiple toasts are visible --}}
    <div x-show="toasts.length > 1" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="flex justify-end pr-1 pointer-events-auto">
        <button type="button" 
                @click="dismissAll()" 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold text-base-content/70 hover:text-base-content bg-base-100/90 dark:bg-base-200/90 hover:bg-base-200 backdrop-blur-md rounded-full border border-base-300 shadow-sm transition-all hover:shadow">
            <span>{{ __('Tutup Semua') }}</span>
            <span class="badge badge-xs bg-base-300/80 text-base-content/80 font-bold px-1.5" x-text="toasts.length"></span>
        </button>
    </div>

    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-8"
             class="pointer-events-auto bg-base-100/95 dark:bg-base-100/90 backdrop-blur-md text-base-content shadow-xl border flex flex-col w-80 sm:w-[390px] max-w-[calc(100vw-1.5rem)] rounded-2xl relative overflow-hidden transition-all duration-200"
             :class="toast.isRejected ? 'border-error/30 ring-1 ring-error/10' : (toast.isApproved ? 'border-success/30 ring-1 ring-success/10' : 'border-primary/30 ring-1 ring-primary/10')">
            
            <!-- Left status bar -->
            <div class="absolute left-0 top-0 bottom-0 w-1" :class="toast.isRejected ? 'bg-error' : (toast.isApproved ? 'bg-success' : 'bg-primary')"></div>

            <div class="p-3 sm:p-3.5 pl-4 flex items-start gap-3">
                <!-- Icon -->
                <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ring-2 shadow-xs mt-0.5" 
                     :class="toast.isRejected ? 'bg-error/15 text-error ring-error/20' : (toast.isApproved ? 'bg-success/15 text-success ring-success/20' : 'bg-primary/15 text-primary ring-primary/20')">
                    <template x-if="toast.isRejected">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="!toast.isRejected && toast.isApproved">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="!toast.isRejected && !toast.isApproved">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </template>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <!-- Top header row -->
                    <div class="flex items-center justify-between gap-1.5 mb-1">
                        <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                            <h3 class="font-bold text-xs leading-snug truncate" :class="toast.isRejected ? 'text-error' : 'text-base-content'" x-text="toast.title"></h3>
                            
                            {{-- Status Badges --}}
                            <template x-if="toast.isRejected">
                                <span class="badge badge-error badge-xs font-bold text-white uppercase shrink-0">{{ __('Ditolak') }}</span>
                            </template>
                            <template x-if="toast.isApproved">
                                <span class="badge badge-success badge-xs font-bold text-white uppercase shrink-0">{{ __('Disetujui') }}</span>
                            </template>
                            <template x-if="(toast.type || '').includes('stamp_request')">
                                <span class="badge badge-secondary badge-xs font-bold uppercase shrink-0">{{ __('Stempel') }}</span>
                            </template>
                            <template x-if="(toast.type || '').includes('signature_request') && !(toast.type || '').includes('stamp') && !toast.isApproved && !toast.isRejected">
                                <span class="badge badge-primary badge-xs font-bold uppercase shrink-0">{{ __('TTD') }}</span>
                            </template>
                        </div>
                        <span x-show="toast.time" class="text-[10px] text-base-content/40 font-normal whitespace-nowrap shrink-0" x-text="toast.time"></span>
                    </div>

                    <!-- Message Body -->
                    <p class="text-xs text-base-content/75 leading-relaxed break-words [overflow-wrap:anywhere]" :class="toast.expanded ? '' : 'line-clamp-2'" x-text="toast.message"></p>

                    <!-- Expand toggle for long message -->
                    <template x-if="toast.message && toast.message.length > 90">
                        <button type="button" 
                                @click="toast.expanded = !toast.expanded" 
                                class="mt-0.5 text-[11px] font-semibold text-primary hover:underline flex items-center gap-0.5">
                            <span x-text="toast.expanded ? '{{ __('Sembunyikan') }}' : '{{ __('Selengkapnya') }}'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="toast.expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </template>

                    <!-- Compact Document Chip (only when not redundant or extra info exists) -->
                    <template x-if="toast.document_title && !toast.message.includes(toast.document_title)">
                        <div class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-base-200/70 dark:bg-base-300/40 border border-base-300/60 max-w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-[11px] font-medium text-base-content truncate" x-text="toast.document_title"></span>
                            <template x-if="toast.document_number">
                                <span class="text-[10px] text-base-content/50 font-mono shrink-0" x-text="`(${toast.document_number})`"></span>
                            </template>
                        </div>
                    </template>

                    <!-- Reason Block (when rejection reason exists) -->
                    <template x-if="toast.reason">
                        <div class="mt-2 p-2 rounded-xl bg-error/10 border border-error/20 text-xs transition-all min-w-0 max-w-full overflow-hidden">
                            <div class="flex items-center gap-1 text-error font-bold text-[10px] mb-0.5 uppercase tracking-wider">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>{{ __('Alasan Penolakan') }}:</span>
                            </div>
                            <div class="text-base-content/85 dark:text-base-content/90 font-normal leading-relaxed break-words [overflow-wrap:anywhere] text-[11px] whitespace-pre-wrap max-h-20 overflow-y-auto pr-1"
                                 x-text="toast.reason">
                            </div>
                        </div>
                    </template>

                    <!-- Action Link / Button -->
                    <div class="mt-2.5 flex items-center justify-between gap-2" x-show="toast.url && toast.url !== '#'">
                        <a :href="formatUrl(toast.url)" 
                           @click="markAsRead(toast.id)"
                           class="btn btn-xs rounded-lg font-bold uppercase gap-1 shadow-2xs h-6.5 min-h-0 text-[10px] px-2.5" 
                           :class="toast.isRejected ? 'btn-error text-white' : (toast.isApproved ? 'btn-success text-white' : 'btn-primary text-white')">
                            <span x-text="toast.isRejected ? '{{ __('Lihat Dokumen') }}' : '{{ __('Buka Dokumen') }}'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Close Button -->
                <button @click="dismiss(toast.id)" 
                        class="btn btn-ghost btn-xs btn-circle h-6 w-6 min-h-0 text-base-content/40 hover:text-base-content hover:bg-base-200 transition-colors shrink-0 -mr-1 -mt-0.5" 
                        aria-label="{{ __('Tutup') }}">
                    ✕
                </button>
            </div>

            <!-- Bottom Countdown Loading Bar -->
            <div class="w-full h-0.5 bg-base-300/40 dark:bg-base-300/60 absolute bottom-0 left-0 right-0 overflow-hidden">
                <div class="h-full transition-all duration-75 ease-linear shadow-xs"
                     :class="toast.isRejected ? 'bg-error' : (toast.isApproved ? 'bg-success' : 'bg-primary')"
                     :style="`width: ${Math.max(0, Math.min(100, (remainingTime / totalDuration) * 100))}%;`">
                </div>
            </div>
        </div>
    </template>
</div>
@endauth
