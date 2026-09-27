@props(['status'])

<span class="bg-ringside-surface-hover text-ringside-ink inline-flex max-w-full items-center gap-2 px-2 py-1 text-xs leading-5">
    <span
        @class([
            'size-1.5 shrink-0 rounded-full',
            'bg-success' => $status === \App\Enums\EventStatus::Scheduled,
            'bg-ringside-muted' => $status === \App\Enums\EventStatus::Past,
            'bg-ringside-signal' => $status === \App\Enums\EventStatus::Unscheduled,
        ])
        aria-hidden="true"
    ></span>
    {{ $status->label() }}
</span>
