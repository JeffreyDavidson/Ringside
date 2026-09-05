<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamData;
use App\Livewire\TagTeams\Forms\CreateEditForm;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('tag team create and edit form', function (): void {
    it('maps tag team fields and selected roster members to typed application data', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        $manager = Manager::factory()->create();
        $employmentDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'The Road Warriors';
        $form->signature_move = 'Doomsday Device';
        $form->wrestlerA = $wrestlerA->id;
        $form->wrestlerB = $wrestlerB->id;
        $form->managers = [$manager->id];
        $form->employment_date = $employmentDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(TagTeamData::class)
            ->and($data->name)->toBe('The Road Warriors')
            ->and($data->signature_move)->toBe('Doomsday Device')
            ->and($data->wrestlerA->is($wrestlerA))->toBeTrue()
            ->and($data->wrestlerB->is($wrestlerB))->toBeTrue()
            ->and($data->managers?->modelKeys())->toBe([$manager->id])
            ->and($data->employment_date?->equalTo($employmentDate))->toBeTrue();
    });

    it('maps blank optional fields to null and an empty manager selection', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'The Rockers';
        $form->signature_move = '';
        $form->wrestlerA = $wrestlers->firstOrFail()->id;
        $form->wrestlerB = $wrestlers->skip(1)->firstOrFail()->id;
        $form->managers = [];
        $form->employment_date = '';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->signature_move)->toBeNull()
            ->and($data->employment_date)->toBeNull()
            ->and($data->managers?->isEmpty())->toBeTrue();
    });

    it('hydrates and resolves the tag team selected for editing', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(2)->create();
        $wrestlerA = $wrestlers->firstOrFail();
        $wrestlerB = $wrestlers->skip(1)->firstOrFail();
        $manager = Manager::factory()->create();
        $tagTeam = TagTeam::factory()->create([
            'name' => 'The Midnight Express',
            'signature_move' => 'Veg-O-Matic',
        ]);
        $tagTeam->wrestlers()->attach($wrestlers->modelKeys(), ['joined_at' => '2024-01-15']);
        $tagTeam->managers()->attach($manager, ['hired_at' => '2024-01-15']);
        $tagTeam->employments()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($tagTeam);
        $selectedTagTeam = $form->tagTeam();

        // Assert
        expect($form->modelId)->toBe($tagTeam->id)
            ->and($form->name)->toBe('The Midnight Express')
            ->and($form->signature_move)->toBe('Veg-O-Matic')
            ->and($form->wrestlerA)->toBe($wrestlerA->id)
            ->and($form->wrestlerB)->toBe($wrestlerB->id)
            ->and($form->managers)->toBe([$manager->id])
            ->and($form->employment_date)->toBe('2024-01-15')
            ->and($selectedTagTeam->is($tagTeam))->toBeTrue();
    });
});
