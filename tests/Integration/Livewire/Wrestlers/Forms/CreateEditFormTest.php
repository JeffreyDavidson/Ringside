<?php

declare(strict_types=1);

use App\Data\Wrestlers\WrestlerData;
use App\Livewire\Wrestlers\Forms\CreateEditForm;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\Height;
use App\ValueObjects\Weight;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('wrestler create and edit form', function (): void {
    it('maps wrestler fields to typed application data', function (): void {
        // Arrange
        $employmentDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'Bret Hart';
        $form->hometown = 'Calgary, Alberta';
        $form->height_feet = 6;
        $form->height_inches = 0;
        $form->weight = 235;
        $form->signature_move = 'Sharpshooter';
        $form->employment_date = $employmentDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(WrestlerData::class)
            ->and($data->name)->toBe('Bret Hart')
            ->and($data->hometown)->toBe('Calgary, Alberta')
            ->and($data->height)->toEqual(new Height(6, 0))
            ->and($data->weight)->toEqual(new Weight(235))
            ->and($data->signature_move)->toBe('Sharpshooter')
            ->and($data->employment_date?->equalTo($employmentDate))->toBeTrue();
    });

    it('maps blank optional fields to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'Bret Hart';
        $form->hometown = 'Calgary, Alberta';
        $form->height_feet = 6;
        $form->height_inches = 0;
        $form->weight = 235;
        $form->signature_move = '';
        $form->employment_date = '';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->signature_move)->toBeNull()
            ->and($data->employment_date)->toBeNull();
    });

    it('hydrates and resolves the wrestler selected for editing', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create([
            'name' => 'Bret Hart',
            'hometown' => 'Calgary, Alberta',
            'height' => 72,
            'weight' => 235,
            'signature_move' => 'Sharpshooter',
        ]);
        $wrestler->employments()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($wrestler);
        $selectedWrestler = $form->wrestler();

        // Assert
        expect($form->modelId)->toBe($wrestler->id)
            ->and($form->name)->toBe('Bret Hart')
            ->and($form->hometown)->toBe('Calgary, Alberta')
            ->and($form->height_feet)->toBe(6)
            ->and($form->height_inches)->toBe(0)
            ->and($form->weight)->toBe(235)
            ->and($form->signature_move)->toBe('Sharpshooter')
            ->and($form->employment_date)->toBe('2024-01-15')
            ->and($selectedWrestler->is($wrestler))->toBeTrue();
    });
});
