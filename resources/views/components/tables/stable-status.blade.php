@props(['status'])

<span class="bg-ringside-surface-hover text-ringside-ink inline-flex max-w-full items-center gap-2 px-2 py-1 text-xs leading-5">
    <span
        @class([
            'size-1.5 shrink-0 rounded-full',
            'bg-success' => $status === \App\Enums\Stables\StableStatus::Active,
            'bg-warning' => $status === \App\Enums\Stables\StableStatus::Inactive,
            'bg-ringside-signal' => $status === \App\Enums\Stables\StableStatus::PendingEstablishment,
            'bg-ringside-muted' => in_array($status, [\App\Enums\Stables\StableStatus::Unformed, \App\Enums\Stables\StableStatus::Retired], true),
        ])
        aria-hidden="true"
    ></span>
    {{ $status->label() }}
</span>
