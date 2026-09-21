<?php

declare(strict_types=1);

use App\Data\Managers\ManagerData;
use App\Livewire\Managers\Forms\CreateEditForm;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('manager create and edit form', function (): void {
    it('maps manager fields to typed application data', function (): void {
        // Arrange
        $employmentDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Paul';
        $form->last_name = 'Dangerously';
        $form->employment_date = $employmentDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(ManagerData::class)
            ->and($data->first_name)->toBe('Paul')
            ->and($data->last_name)->toBe('Dangerously')
            ->and($data->employment_date?->equalTo($employmentDate))->toBeTrue();
    });

    it('maps a missing employment date to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->first_name = 'Jimmy';
        $form->last_name = 'Hart';
        $form->employment_date = null;

        // Act
        $data = $form->toData();

        // Assert
        expect($data->employment_date)->toBeNull();
    });

    it('hydrates and resolves the manager selected for editing', function (): void {
        // Arrange
        $manager = Manager::factory()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);
        $manager->employments()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($manager);
        $selectedManager = $form->manager();

        // Assert
        expect($form->modelId)->toBe($manager->id)
            ->and($form->first_name)->toBe('Bobby')
            ->and($form->last_name)->toBe('Heenan')
            ->and($form->employment_date)->toBe('2024-01-15')
            ->and($selectedManager->is($manager))->toBeTrue();
    });
});
