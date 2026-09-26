@props(['status'])

<span class="bg-ringside-surface-hover text-ringside-ink inline-flex max-w-full items-center gap-2 px-2 py-1 text-xs leading-5">
    <span
        @class([
            'size-1.5 shrink-0 rounded-full',
            'bg-success' => $status === \App\Enums\Titles\TitleStatus::Active,
            'bg-ringside-signal' => $status === \App\Enums\Titles\TitleStatus::PendingDebut,
            'bg-warning' => $status === \App\Enums\Titles\TitleStatus::Inactive,
            'bg-ringside-muted' => in_array($status, [\App\Enums\Titles\TitleStatus::Undebuted, \App\Enums\Titles\TitleStatus::Retired], true),
        ])
        aria-hidden="true"
    ></span>
    {{ $status->label() }}
</span>
