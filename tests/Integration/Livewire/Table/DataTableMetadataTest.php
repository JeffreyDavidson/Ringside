<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Enums\Titles\TitleStatus;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\Titles\Tables\Main as TitlesTable;
use App\Livewire\Wrestlers\Tables\Main as WrestlersTable;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

describe('table status metadata', function (): void {
    it('counts derived employment statuses through the table status filter', function () {
        // Arrange
        Wrestler::factory()->employed()->create();
        Wrestler::factory()->released()->create();
        Wrestler::factory()->unemployed()->create();

        $table = new WrestlersTable();

        // Act
        $metadata = $table->metadata();

        // Assert
        $statuses = collect($metadata['statuses'])->keyBy('value');

        expect($metadata['total'])->toBe(3)
            ->and($statuses['employed'])->toBe([
                'value' => 'employed',
                'label' => 'Employed',
                'count' => 1,
            ])
            ->and($statuses['released'])->toBe([
                'value' => 'released',
                'label' => 'Released',
                'count' => 1,
            ])
            ->and($statuses['unemployed'])->toBe([
                'value' => 'unemployed',
                'label' => 'Unemployed',
                'count' => 1,
            ]);
    });

    it('uses every title status as a metadata and filter value', function () {
        // Arrange
        Title::factory()->active()->create();
        Title::factory()->retired()->create();
        Title::factory()->withFutureDebut()->create();

        $table = new TitlesTable();

        // Act
        $metadata = $table->metadata();

        // Assert
        $statuses = collect($metadata['statuses'])->keyBy('value');

        expect($statuses->keys()->all())->toBe(
            array_map(
                static fn (TitleStatus $status): string => $status->value,
                TitleStatus::cases(),
            ),
        )
            ->and($statuses->get(TitleStatus::Active->value))->toBe([
                'value' => TitleStatus::Active->value,
                'label' => TitleStatus::Active->label(),
                'count' => 1,
            ])
            ->and($statuses->get(TitleStatus::PendingDebut->value))->toBe([
                'value' => TitleStatus::PendingDebut->value,
                'label' => TitleStatus::PendingDebut->label(),
                'count' => 1,
            ])
            ->and($statuses->get(TitleStatus::Retired->value))->toBe([
                'value' => TitleStatus::Retired->value,
                'label' => TitleStatus::Retired->label(),
                'count' => 1,
            ]);
    });

    it('uses every stable status as a metadata and filter value', function () {
        // Arrange
        Stable::factory()->active()->create();
        Stable::factory()->retired()->create();
        Stable::factory()->withFutureActivation()->create();

        $table = new StablesTable();

        // Act
        $metadata = $table->metadata();

        // Assert
        $statuses = collect($metadata['statuses'])->keyBy('value');

        expect($statuses->keys()->all())->toBe(
            array_map(
                static fn (StableStatus $status): string => $status->value,
                StableStatus::cases(),
            ),
        )
            ->and($statuses->get(StableStatus::Active->value))->toBe([
                'value' => StableStatus::Active->value,
                'label' => StableStatus::Active->label(),
                'count' => 1,
            ])
            ->and($statuses->get(StableStatus::PendingEstablishment->value))->toBe([
                'value' => StableStatus::PendingEstablishment->value,
                'label' => StableStatus::PendingEstablishment->label(),
                'count' => 1,
            ])
            ->and($statuses->get(StableStatus::Retired->value))->toBe([
                'value' => StableStatus::Retired->value,
                'label' => StableStatus::Retired->label(),
                'count' => 1,
            ]);
    });
});
