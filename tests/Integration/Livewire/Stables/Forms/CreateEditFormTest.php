<?php

declare(strict_types=1);

use App\Data\Stables\StableData;
use App\Livewire\Stables\Forms\CreateEditForm;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('stable create and edit form', function (): void {
    it('maps stable fields and selected members to typed application data', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $tagTeam = TagTeam::factory()->create();
        $startDate = Date::parse('2024-01-01');
        $endDate = Date::parse('2024-12-31');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'The Four Horsemen';
        $form->started_at = $startDate->toDateString();
        $form->ended_at = $endDate->toDateString();
        $form->wrestlers = $wrestlers->modelKeys();
        $form->tag_teams = [$tagTeam->id];

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(StableData::class)
            ->and($data->name)->toBe('The Four Horsemen')
            ->and($data->start_date?->equalTo($startDate))->toBeTrue()
            ->and($data->end_date?->equalTo($endDate))->toBeTrue()
            ->and($data->members->wrestlers?->map(fn (Wrestler $wrestler): int => $wrestler->id)->all())
            ->toBe($wrestlers->modelKeys())
            ->and($data->members->tagTeams?->map(fn (TagTeam $selectedTagTeam): int => $selectedTagTeam->id)->all())
            ->toBe([$tagTeam->id]);
    });

    it('maps missing activity dates and member selections to empty values', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'Future Faction';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->start_date)->toBeNull()
            ->and($data->end_date)->toBeNull()
            ->and($data->members->wrestlers?->isEmpty())->toBeTrue()
            ->and($data->members->tagTeams?->isEmpty())->toBeTrue();
    });

    it('hydrates and resolves the stable selected for editing', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        $tagTeam = TagTeam::factory()->create();
        $stable = Stable::factory()->create(['name' => 'The Dangerous Alliance']);
        $stable->activityPeriods()->create([
            'started_at' => '2024-01-01',
            'ended_at' => '2024-12-31',
        ]);
        $stable->wrestlers()->attach($wrestler, ['joined_at' => '2024-01-01']);
        $stable->tagTeams()->attach($tagTeam, ['joined_at' => '2024-01-01']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($stable);
        $selectedStable = $form->stable();

        // Assert
        expect($form->modelId)->toBe($stable->id)
            ->and($form->name)->toBe('The Dangerous Alliance')
            ->and($form->started_at)->toBe('2024-01-01')
            ->and($form->ended_at)->toBe('2024-12-31')
            ->and($form->wrestlers)->toBe([$wrestler->id])
            ->and($form->tag_teams)->toBe([$tagTeam->id])
            ->and($selectedStable->is($stable))->toBeTrue();
    });
});
