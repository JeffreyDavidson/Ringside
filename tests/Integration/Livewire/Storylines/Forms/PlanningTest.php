<?php

declare(strict_types=1);

use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\ParticipantData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Data\Storylines\StorylineData;
use App\Livewire\Storylines\Forms\Planning;
use Illuminate\Validation\ValidationException;
use JMac\Testing\Double;
use Livewire\Component;

describe('planning form source', function (): void {
    it('maps the chosen source fields to typed application data', function (): void {
        // Arrange
        $form = new Planning(Double::for(Component::class), 'form');
        $form->eventId = 7;
        $form->matchId = 9;

        // Act
        $source = $form->source('fingerprint');

        // Assert
        expect($source)->toBeInstanceOf(SourceData::class)
            ->and($source->eventId)->toBe(7)
            ->and($source->matchId)->toBe(9)
            ->and($source->segmentId)->toBeNull()
            ->and($source->targetOn)->toBeNull()
            ->and($source->fingerprint)->toBe('fingerprint');
    });

    it('treats blank segment and date fields as not chosen', function (): void {
        // Arrange
        $form = new Planning(Double::for(Component::class), 'form');
        $form->segmentId = '';
        $form->targetOn = '';

        // Act
        $source = $form->source(null);

        // Assert
        expect($source->segmentId)->toBeNull()
            ->and($source->targetOn)->toBeNull();
    });

    it('rejects source fields chosen in an invalid combination', function (array $fields, string $field): void {
        // Arrange
        $form = new Planning(Double::for(Component::class), 'form');
        foreach ($fields as $property => $value) {
            $form->{$property} = $value;
        }

        // Act
        $errors = null;
        try {
            $form->source(null);
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
        }

        // Assert
        expect($errors)->not->toBeNull()
            ->and(array_keys($errors ?? []))->toBe([$field]);
    })->with([
        'match without event' => [['matchId' => 1], 'event'],
        'segment without event' => [['segmentId' => '1f64fc86-5bbf-4a41-952d-b85800358f00'], 'event'],
        'both match and segment' => [['eventId' => 1, 'matchId' => 1, 'segmentId' => '1f64fc86-5bbf-4a41-952d-b85800358f00'], 'match'],
        'event and standalone date' => [['eventId' => 1, 'targetOn' => '2026-10-13'], 'target'],
        'malformed standalone date' => [['targetOn' => '13/10/2026'], 'target'],
    ]);
});

describe('planning form data', function (): void {
    it('builds each Action input from the filled form', function (): void {
        // Arrange
        $form = new Planning(Double::for(Component::class), 'form');
        $form->heading = 'The angle';
        $form->description = 'The argument.';
        $form->payoff = 'The finish.';
        $form->occurredOn = '2026-10-13';
        $form->sequence = 3;
        $form->participantIds = [4, 5];
        $form->eventId = 7;
        $form->type = 'tag_team';
        $form->participantId = 9;
        $form->role = 'Allies';
        $form->joinedOn = '2026-10-01';
        $form->leftOn = '2026-10-12';
        $form->reason = 'Clarified.';

        // Act
        $story = $form->storyData('submission');
        $step = $form->stepData('fingerprint', 'submission');
        $development = $form->developmentData('fingerprint', 'submission', 11);
        $participant = $form->participantData();

        // Assert
        expect($story)->toEqual(new StorylineData('The angle', 'The argument.', 'The finish.', 'submission'))
            ->and($step)->toEqual(new StepData('The angle', 'The argument.', 3, new SourceData(7, null, null, null, 'fingerprint'), 'submission', 'Clarified.'))
            ->and($development)->toEqual(new DevelopmentData('The angle', 'The argument.', '2026-10-13', 3, [4, 5], new SourceData(7, null, null, null, 'fingerprint'), 'submission', 11, 'Clarified.'))
            ->and($participant)->toEqual(new ParticipantData('tag_team', 9, 'Allies', '2026-10-01', '2026-10-12', 'Clarified.'));
    });

    it('sends blank optional fields as not given', function (): void {
        // Arrange
        $form = new Planning(Double::for(Component::class), 'form');

        // Act
        $step = $form->stepData(null, 'submission');
        $participant = $form->participantData();

        // Assert
        expect($step->reason)->toBeNull()
            ->and($participant->id)->toBe(0)
            ->and($participant->role)->toBeNull()
            ->and($participant->reason)->toBeNull();
    });
});
