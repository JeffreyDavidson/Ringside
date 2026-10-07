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
        'employed' => [EmploymentStatus::Employed, 'bg-success'],
        'awaiting employment' => [EmploymentStatus::FutureEmployment, 'bg-warning'],
        'released' => [EmploymentStatus::Released, 'bg-ringside-muted'],
        'scheduled event' => [EventStatus::Scheduled, 'bg-success'],
        'past event' => [EventStatus::Past, 'bg-ringside-muted'],
        'unscheduled event' => [EventStatus::Unscheduled, 'bg-ringside-signal'],
        'active stable' => [StableStatus::Active, 'bg-success'],
        'inactive stable' => [StableStatus::Inactive, 'bg-warning'],
        'pending stable' => [StableStatus::PendingEstablishment, 'bg-ringside-signal'],
        'retired stable' => [StableStatus::Retired, 'bg-ringside-muted'],
        'active title' => [TitleStatus::Active, 'bg-success'],
        'inactive title' => [TitleStatus::Inactive, 'bg-warning'],
        'pending title' => [TitleStatus::PendingDebut, 'bg-ringside-signal'],
        'undebuted title' => [TitleStatus::Undebuted, 'bg-ringside-muted'],
    ]);
});
