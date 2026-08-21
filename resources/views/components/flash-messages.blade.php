<div
    class="pt-5"
    data-notification-type="{{ session()->has('error') ? 'error' : 'status' }}"
    data-notification-message="{{ session('error') ?? session('status') }}"
    x-data="{
        notification: $el.dataset.notificationMessage
            ? {
                type: $el.dataset.notificationType,
                message: $el.dataset.notificationMessage,
            }
            : null,
    }"
    x-on:flash-message.window="notification = $event.detail"
    x-show="notification !== null"
    x-cloak
>
    <x-container-fixed>
        <div
            class="flex items-center justify-between gap-4 rounded border px-4 py-3 text-sm"
            x-bind:class="notification?.type === 'error'
                ? 'border-danger bg-danger-light text-danger'
                : 'border-success bg-success-light text-success'"
            role="{{ session()->has('error') ? 'alert' : 'status' }}"
            x-bind:role="notification?.type === 'error' ? 'alert' : 'status'"
            aria-live="{{ session()->has('error') ? 'assertive' : 'polite' }}"
            x-bind:aria-live="notification?.type === 'error' ? 'assertive' : 'polite'"
        >
            <span x-text="notification?.message">{{ session('error') ?? session('status') }}</span>

            <button
                type="button"
                class="shrink-0 rounded p-1 transition hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                aria-label="Dismiss notification"
                x-on:click="notification = null"
            >
                <x-heroicon-m-x-mark class="size-4" aria-hidden="true" />
            </button>
        </div>
    </x-container-fixed>
</div>
