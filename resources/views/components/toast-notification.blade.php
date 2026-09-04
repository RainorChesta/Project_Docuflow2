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
        
        <div class="pointer-events-auto bg-base-100/95 dark:bg-base-100/90 backdrop-blur-md border border-warning/40 text-base-content shadow-2xl flex flex-row items-start gap-3.5 w-84 sm:w-96 rounded-2xl relative overflow-hidden p-4 pb-5">
            <!-- Decorative left border -->
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-warning"></div>
            
            <div class="w-10 h-10 rounded-2xl bg-warning/10 text-warning flex items-center justify-center shrink-0 ring-4 ring-warning/5 shadow-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            
            <div class="flex-1 min-w-0 py-0.5">
                <h3 class="font-bold text-sm text-base-content">{{ __('Perhatian: Dokumen Mendesak') }}</h3>
                <p class="text-xs text-base-content/70 mt-1 leading-relaxed">
                    {{ __('Terdapat :count dokumen yang akan kedaluwarsa dalam 3 hari ke depan.', ['count' => session('urgent_expiring_count')]) }}
                </p>
                <div class="mt-2.5">
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-warning hover:text-warning/80 transition-colors inline-flex items-center gap-1.5 hover:underline">
                        {{ __('Lihat Daftar Dokumen') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
            
            <button @click="show = false; clearInterval(timer)" class="btn btn-ghost btn-xs btn-circle text-base-content/40 hover:text-base-content hover:bg-base-200 transition-colors" aria-label="{{ __('Tutup') }}">
                ✕
            </button>

            <!-- Bottom Countdown Loading / Progress Bar -->
            <div class="w-full h-1 bg-warning/20 absolute bottom-0 left-0 right-0 overflow-hidden">
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
        totalDuration: 9000,
        remainingTime: 9000,
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
                    const currentToastIds = this.toasts.map(t => t.id);
                    const incomingToasts = newNotifs
                        .filter(n => !currentToastIds.includes(n.id))
                        .map(notif => {
                            shown.push(notif.id);
                            return {
                                id: notif.id,
                                title: notif.title,
                                message: notif.message,
                                reason: notif.reason,
                                url: this.formatUrl(notif.url),
                                time: notif.time,
                                isRejected: notif.icon === 'rejected' || (notif.type || '').includes('reject') || (notif.type || '').includes('revoked'),
                                expanded: false
                            };
                        });

                    if (incomingToasts.length > 0) {
                        this.toasts = [...incomingToasts, ...this.toasts].slice(0, 4);
                        localStorage.setItem(storageKey, JSON.stringify(shown.slice(-100)));
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
     class="fixed top-16 right-3 sm:right-5 z-[100] flex flex-col gap-3 pointer-events-none max-w-full">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-x-0"
             x-transition:leave-end="opacity-0 scale-95 translate-x-8"
             class="pointer-events-auto bg-base-100/95 dark:bg-base-100/90 backdrop-blur-md text-base-content shadow-2xl border flex flex-col w-84 sm:w-[410px] max-w-[calc(100vw-1.5rem)] rounded-2xl relative overflow-hidden transition-all duration-200"
             :class="toast.isRejected ? 'border-error/30 ring-1 ring-error/10' : 'border-success/30 ring-1 ring-success/10'">
            
            <!-- Left status bar -->
            <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="toast.isRejected ? 'bg-error' : 'bg-success'"></div>

            <div class="p-4 sm:p-4.5 pl-5 pb-5 flex items-start gap-3.5">
                <!-- Icon -->
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 ring-4 shadow-xs" 
                     :class="toast.isRejected ? 'bg-error/10 text-error ring-error/5' : 'bg-success/10 text-success ring-success/5'">
                    <template x-if="toast.isRejected">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="!toast.isRejected">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 pr-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-sm leading-snug" :class="toast.isRejected ? 'text-error' : 'text-base-content'" x-text="toast.title"></h3>
                        <span x-show="toast.time" class="text-[10px] text-base-content/40 font-normal" x-text="toast.time"></span>
                    </div>

                    <p class="text-xs text-base-content/70 mt-1 leading-relaxed break-words" :class="toast.expanded ? '' : 'line-clamp-2'" x-text="toast.message"></p>

                    <!-- Reason Block (when rejection reason exists) -->
                    <template x-if="toast.reason">
                        <div class="mt-2.5 p-3 rounded-xl bg-error/10 border border-error/20 text-xs transition-all">
                            <div class="flex items-center gap-1.5 text-error font-semibold text-[11px] mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                <span>{{ __('Alasan Penolakan') }}:</span>
                            </div>
                            
                            <!-- Reason Text with Clamp / Expand -->
                            <div class="text-base-content/85 dark:text-base-content/90 font-normal leading-relaxed break-words text-xs whitespace-pre-line"
                                 :class="toast.expanded ? 'max-h-40 overflow-y-auto pr-1' : 'line-clamp-2'"
                                 x-text="toast.reason">
                            </div>

                            <!-- Expand/Collapse Toggle if text is long -->
                            <template x-if="toast.reason && toast.reason.length > 75">
                                <button type="button" 
                                        @click="toast.expanded = !toast.expanded" 
                                        class="mt-1.5 text-[11px] font-semibold text-error hover:underline flex items-center gap-1">
                                    <span x-text="toast.expanded ? '{{ __('Sembunyikan') }}' : '{{ __('Lihat selengkapnya') }}'"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="toast.expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </template>

                    <!-- Action Link -->
                    <div class="mt-3 flex items-center gap-3" x-show="toast.url && toast.url !== '#'">
                        <a :href="formatUrl(toast.url)" 
                           @click="markAsRead(toast.id)"
                           class="text-xs font-semibold hover:underline inline-flex items-center gap-1 transition-colors" 
                           :class="toast.isRejected ? 'text-error' : 'text-success'">
                            <span x-text="toast.isRejected ? '{{ __('Lihat Dokumen') }}' : '{{ __('Buka Dokumen') }}'"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Close Button -->
                <button @click="dismiss(toast.id)" 
                        class="btn btn-ghost btn-xs btn-circle text-base-content/40 hover:text-base-content hover:bg-base-200 transition-colors shrink-0" 
                        aria-label="{{ __('Tutup') }}">
                    ✕
                </button>
            </div>

            <!-- Bottom Countdown Loading Bar (shrinks as dismissal timer elapses) -->
            <div class="w-full h-1 bg-base-300/40 dark:bg-base-300/60 absolute bottom-0 left-0 right-0 overflow-hidden">
                <div class="h-full transition-all duration-75 ease-linear shadow-xs"
                     :class="toast.isRejected ? 'bg-error' : 'bg-success'"
                     :style="`width: ${Math.max(0, Math.min(100, (remainingTime / totalDuration) * 100))}%;`">
                </div>
            </div>
        </div>
    </template>
</div>
@endauth


