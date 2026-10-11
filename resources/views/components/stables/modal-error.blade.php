@error('stable')
    <div
        class="border-ringside-error bg-ringside-error-surface text-ringside-ink flex items-start gap-3 border px-4 py-3 text-sm"
        role="alert"
    >
        <x-heroicon-s-exclamation-circle class="text-ringside-error mt-0.5 size-4 shrink-0" aria-hidden="true" />
        <span>{{ $message }}</span>
    </div>
@enderror
