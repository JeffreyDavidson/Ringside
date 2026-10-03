@php
    $notificationMessage = session('error') ?? session('status');
    if ($notificationMessage !== null && ! is_string($notificationMessage)) {
        throw new \UnexpectedValueException('Flash notifications must contain a string message.');
    }
@endphp

{{-- The live regions stay rendered so screen readers notice each message that is written into them. --}}
<div
    data-notification-type="{{ session()->has('error') ? 'error' : 'status' }}"
    data-notification-message="{{ $notificationMessage }}"
    x-data="{
        notification: null,
        announcement: { status: '', alert: '' },
        announcementTimer: null,
        init() {
            if (this.$el.dataset.notificationMessage) {
                this.show({
                    type: this.$el.dataset.notificationType,
                    message: this.$el.dataset.notificationMessage,
                });
            }
        },
        show(notification) {
            this.notification = notification;
            this.announcement = { status: '', alert: '' };

            clearTimeout(this.announcementTimer);
            this.announcementTimer = setTimeout(() => {
                this.announcement[notification.type === 'error' ? 'alert' : 'status'] = notification.message;
            }, 100);
        },
    }"
    x-on:flash-message.window="show($event.detail)"
>
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true" data-test="flash-status-region">
        <span x-text="announcement.status"></span>
    </div>
    <div class="sr-only" role="alert" aria-live="assertive" aria-atomic="true" data-test="flash-alert-region">
        <span x-text="announcement.alert"></span>
    </div>

    <div class="pt-5" x-show="notification !== null" x-cloak>
        <x-container-fixed>
            <div
                class="bg-ringside-surface-panel text-ringside-ink flex items-center justify-between gap-4 border border-s-4 px-4 py-3 text-sm"
                x-bind:class="
                    notification?.type === 'error' ? 'border-ringside-signal-soft' : 'border-ringside-success'
                "
            >
                <span class="flex items-start gap-3">
                    <x-heroicon-s-exclamation-circle
                        x-show="notification?.type === 'error'"
                        class="text-ringside-signal-soft mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <x-heroicon-s-check-circle
                        x-show="notification?.type !== 'error'"
                        class="text-ringside-success mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <span x-text="notification?.message">{{ $notificationMessage }}</span>
                </span>

                <button
                    type="button"
                    class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex size-9 shrink-0 items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2"
                    aria-label="Dismiss notification"
                    x-on:click="notification = null"
                >
                    <x-heroicon-m-x-mark class="size-4" aria-hidden="true" />
                </button>
            </div>
        </x-container-fixed>
    </div>
</div>
