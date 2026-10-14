<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Stables\StableStatus;
use App\Enums\Titles\TitleStatus;
use Illuminate\Support\Facades\Blade;

describe('table status badge', function (): void {
    it('renders the enum label with the matching dot colour', function (EmploymentStatus|EventStatus|StableStatus|TitleStatus $status, string $dotClass): void {
        $html = Blade::render('<x-tables.status :status="$status" />', ['status' => $status]);

        expect($html)
            ->toContain($status->label())
            ->toContain($dotClass.' size-1.5 shrink-0 rounded-full');
    })->with([
        'employed' => [EmploymentStatus::Employed, 'bg-legacy-success'],
        'awaiting employment' => [EmploymentStatus::FutureEmployment, 'bg-legacy-warning'],
        'released' => [EmploymentStatus::Released, 'bg-muted-foreground'],
        'scheduled event' => [EventStatus::Scheduled, 'bg-legacy-success'],
        'past event' => [EventStatus::Past, 'bg-muted-foreground'],
        'unscheduled event' => [EventStatus::Unscheduled, 'bg-signal'],
        'active stable' => [StableStatus::Active, 'bg-legacy-success'],
        'inactive stable' => [StableStatus::Inactive, 'bg-legacy-warning'],
        'pending stable' => [StableStatus::PendingEstablishment, 'bg-signal'],
        'retired stable' => [StableStatus::Retired, 'bg-muted-foreground'],
        'active title' => [TitleStatus::Active, 'bg-legacy-success'],
        'inactive title' => [TitleStatus::Inactive, 'bg-legacy-warning'],
        'pending title' => [TitleStatus::PendingDebut, 'bg-signal'],
        'undebuted title' => [TitleStatus::Undebuted, 'bg-muted-foreground'],
    ]);
});
