<?php

declare(strict_types=1);

use App\Data\Referees\RefereeData;
use App\Livewire\Referees\Forms\CreateEditForm;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('referee create and edit form', function (): void {
    it('maps referee fields to typed application data', function (): void {
        // Arrange
        $employmentDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Mike';
        $form->last_name = 'Chioda';
        $form->employment_date = $employmentDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(RefereeData::class)
            ->and($data->first_name)->toBe('Mike')
            ->and($data->last_name)->toBe('Chioda')
            ->and($data->employment_date?->equalTo($employmentDate))->toBeTrue();
    });

    it('maps a missing employment date to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Charles';
        $form->last_name = 'Robinson';
        $form->employment_date = null;

        // Act
        $data = $form->toData();

        // Assert
        expect($data->employment_date)->toBeNull();
    });

    it('hydrates and resolves the referee selected for editing', function (): void {
        // Arrange
        $referee = Referee::factory()->create([
            'first_name' => 'Earl',
            'last_name' => 'Hebner',
        ]);
        $referee->employments()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($referee);
        $selectedReferee = $form->referee();

        // Assert
        expect($form->modelId)->toBe($referee->id)
            ->and($form->first_name)->toBe('Earl')
            ->and($form->last_name)->toBe('Hebner')
            ->and($form->employment_date)->toBe('2024-01-15')
            ->and($selectedReferee->is($referee))->toBeTrue();
    });
});
