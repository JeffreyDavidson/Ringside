<?php

declare(strict_types=1);

use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Livewire\Titles\Forms\CreateEditForm;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Date;
use JMac\Testing\Double;
use Livewire\Component;

describe('title create and edit form', function (): void {
    it('maps title fields to typed application data', function (): void {
        // Arrange
        $debutDate = Date::parse('2024-02-01');
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'World Championship Title';
        $form->type = TitleType::Singles->value;
        $form->start_date = $debutDate->toDateString();

        // Act
        $data = $form->toData();

        // Assert
        expect($data)->toBeInstanceOf(TitleData::class)
            ->and($data->name)->toBe('World Championship Title')
            ->and($data->type)->toBe(TitleType::Singles)
            ->and($data->debut_date?->equalTo($debutDate))->toBeTrue();
    });

    it('maps a blank debut date to null', function (): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->name = 'World Tag Team Titles';
        $form->type = TitleType::TagTeam->value;
        $form->start_date = '';

        // Act
        $data = $form->toData();

        // Assert
        expect($data->debut_date)->toBeNull();
    });

    it('hydrates and resolves the title selected for editing', function (): void {
        // Arrange
        $title = Title::factory()->singles()->create([
            'name' => 'Intercontinental Championship Title',
        ]);
        $title->activityPeriods()->create(['started_at' => '2024-01-15']);
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->setModel($title);
        $selectedTitle = $form->title();

        // Assert
        expect($form->modelId)->toBe($title->id)
            ->and($form->name)->toBe('Intercontinental Championship Title')
            ->and($form->type)->toBe(TitleType::Singles->value)
            ->and($form->start_date)->toBe('2024-01-15')
            ->and($selectedTitle->is($title))->toBeTrue();
    });
});
