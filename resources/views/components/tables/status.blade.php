@props(['status'])

@php
    use App\Enums\EventStatus;
    use App\Enums\Shared\EmploymentStatus;
    use App\Enums\Stables\StableStatus;
    use App\Enums\Titles\TitleStatus;

    $dotClass = match ($status) {
        EmploymentStatus::Employed, EventStatus::Scheduled, StableStatus::Active, TitleStatus::Active => 'bg-status-positive',
        EmploymentStatus::FutureEmployment, StableStatus::Inactive, TitleStatus::Inactive => 'bg-status-caution',
        EventStatus::Unscheduled, StableStatus::PendingEstablishment, TitleStatus::PendingDebut => 'bg-signal',
        default => 'bg-muted-foreground',
    };
@endphp

<span class="bg-surface-hover text-foreground inline-flex max-w-full items-center gap-2 px-2 py-1 text-xs leading-5">
    <span class="{{ $dotClass }} size-1.5 shrink-0 rounded-full" aria-hidden="true"></span>
    {{ $status->label() }}
</span>
