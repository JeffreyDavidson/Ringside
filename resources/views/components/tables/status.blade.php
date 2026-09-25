@props(['status'])

<span class="bg-ringside-surface-hover text-ringside-ink inline-flex max-w-full items-center gap-2 px-2 py-1 text-xs leading-5">
    <span
        @class([
            'size-1.5 shrink-0 rounded-full',
            'bg-success' => $status === \App\Enums\Shared\EmploymentStatus::Employed,
            'bg-warning' => $status === \App\Enums\Shared\EmploymentStatus::FutureEmployment,
            'bg-ringside-muted' => ! in_array($status, [\App\Enums\Shared\EmploymentStatus::Employed, \App\Enums\Shared\EmploymentStatus::FutureEmployment], true),
        ])
        aria-hidden="true"
    ></span>
    {{ $status->label() }}
</span>
