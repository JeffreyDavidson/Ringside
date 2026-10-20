<?php

declare(strict_types=1);

use App\Data\Tournaments\TournamentBookingData;
use App\Enums\Tournaments\EntrantKind;
use App\Enums\Tournaments\TournamentOperation;
use App\Livewire\Tournaments\Forms\TournamentOperationForm;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JMac\Testing\Double;
use Livewire\Component;

/**
 * A form wired like Livewire does on every request, started for the fixture tournament's draft operation.
 *
 * @param  array<string, mixed>  $world
 */
function startedOperationForm(array $world, TournamentOperation $operation, ?int $eventId = null): TournamentOperationForm
{
    $form = new TournamentOperationForm(Double::for(Component::class), 'draft');
    $form->start($world['tournament'], $operation, $eventId);

    return $form;
}

/** @return list<string> the failing fields, without the form's "draft." prefix */
function operationFormErrorKeys(TournamentOperationForm $form): array
{
    try {
        $form->validate();
    } catch (ValidationException $exception) {
        return array_map(fn (string $key): string => Str::after($key, 'draft.'), array_keys($exception->errors()));
    }

    return [];
}

describe('tournament operation form', function (): void {
    it('starts a draft with the node event and cleared inputs', function (): void {
        // Arrange
        $world = tournamentFixture();
        $form = startedOperationForm($world, TournamentOperation::Book, $world['event']->id);
        $form->reason = 'Left over';
        $form->confirmed = true;

        // Act
        $form->start($world['tournament'], TournamentOperation::Plan, null);

        // Assert
        expect($form->operation)->toBe('plan')
            ->and($form->eventId)->toBeNull()
            ->and($form->reason)->toBe('')
            ->and($form->confirmed)->toBeFalse()
            ->and($form->refereeIds)->toBe([])
            ->and($form->promotionId)->toBe($world['promotion']->id)
            ->and($form->entrantKind)->toBe('wrestler');
    });

    it('requires an event and a referee to book', function (): void {
        // Arrange
        $form = startedOperationForm(tournamentFixture(), TournamentOperation::Book);

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toEqualCanonicalizing(['eventId', 'refereeIds']);
    });

    it('books with an event from the tournament promotion and a referee', function (): void {
        // Arrange
        $world = tournamentFixture();
        $form = startedOperationForm($world, TournamentOperation::Book, $world['event']->id);
        $form->refereeIds = [$world['referees']->sole()->id];

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe([]);
    });

    it('refuses to book with an event or title from another promotion, a repeated referee or an unknown stipulation', function (): void {
        // Arrange
        $world = tournamentFixture();
        $other = tournamentFixture();
        $form = startedOperationForm($world, TournamentOperation::Book, $other['event']->id);
        $referee = $world['referees']->sole();
        $form->refereeIds = [$referee->id, $referee->id];
        $form->titleId = Title::factory()->for($other['promotion'])->create()->id;
        $form->stipulationId = 0;

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toEqualCanonicalizing(['eventId', 'refereeIds.0', 'refereeIds.1', 'titleId', 'stipulationId']);
    });

    it('does not require an event to plan', function (): void {
        // Arrange
        $form = startedOperationForm(tournamentFixture(), TournamentOperation::Plan);

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe([]);
    });

    it('refuses to plan with an event from another promotion', function (): void {
        // Arrange
        $world = tournamentFixture();
        $other = tournamentFixture();
        $form = startedOperationForm($world, TournamentOperation::Plan, $other['event']->id);

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe(['eventId']);
    });

    it('requires a replacement of the tournament entrant kind to replace', function (EntrantKind $kind): void {
        // Arrange
        $world = tournamentFixture(kind: $kind);
        $form = startedOperationForm($world, TournamentOperation::Replace);
        $form->reason = 'Injured';
        $form->confirmed = true;

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe(['replacementId']);
    })->with(EntrantKind::cases());

    it('refuses a replacement from another promotion', function (EntrantKind $kind): void {
        // Arrange
        $world = tournamentFixture(kind: $kind);
        $other = tournamentFixture(kind: $kind);
        $form = startedOperationForm($world, TournamentOperation::Replace);
        $form->reason = 'Injured';
        $form->confirmed = true;
        $form->replacementId = $other['tournament']->entries()->firstOrFail()->competitor_id;

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe(['replacementId']);
    })->with(EntrantKind::cases());

    it('accepts a replacement from the tournament promotion', function (EntrantKind $kind): void {
        // Arrange
        $world = tournamentFixture(kind: $kind);
        $form = startedOperationForm($world, TournamentOperation::Replace);
        $form->reason = 'Injured';
        $form->confirmed = true;
        $form->replacementId = $kind === EntrantKind::Wrestler
            ? Wrestler::factory()->employed()->for($world['promotion'])->create()->id
            : TagTeam::factory()->bookable()->for($world['promotion'])->create()->id;

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe([]);
    })->with(EntrantKind::cases());

    it('requires a reason and a confirmation for every reviewed operation', function (TournamentOperation $operation): void {
        // Arrange
        $form = startedOperationForm(tournamentFixture(), $operation);

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toEqualCanonicalizing(['reason', 'confirmed']);
    })->with([
        TournamentOperation::Reset,
        TournamentOperation::Unbook,
        TournamentOperation::Withdraw,
        TournamentOperation::Walkover,
        TournamentOperation::Cancel,
    ]);

    it('refuses a reason over 2000 characters', function (): void {
        // Arrange
        $form = startedOperationForm(tournamentFixture(), TournamentOperation::Cancel);
        $form->reason = str_repeat('a', 2001);
        $form->confirmed = true;

        // Act
        $keys = operationFormErrorKeys($form);

        // Assert
        expect($keys)->toBe(['reason']);
    });

    it('builds booking data from the drafted ids', function (): void {
        // Arrange
        $world = tournamentFixture();
        $title = Title::factory()->for($world['promotion'])->create();
        $stipulation = MatchStipulation::factory()->create();
        $form = startedOperationForm($world, TournamentOperation::Book, $world['event']->id);
        $form->refereeIds = [$world['referees']->sole()->id];
        $form->titleId = $title->id;
        $form->stipulationId = $stipulation->id;

        // Act
        $data = $form->bookingData();

        // Assert
        expect($data)->toBeInstanceOf(TournamentBookingData::class)
            ->and($data->event->is($world['event']))->toBeTrue()
            ->and($data->referees->modelKeys())->toBe([$world['referees']->sole()->id])
            ->and($data->titles->modelKeys())->toBe([$title->id])
            ->and($data->stipulation?->is($stipulation))->toBeTrue();
    });

    it('builds booking data without a title or stipulation', function (): void {
        // Arrange
        $world = tournamentFixture();
        $form = startedOperationForm($world, TournamentOperation::Book, $world['event']->id);
        $form->refereeIds = [$world['referees']->sole()->id];

        // Act
        $data = $form->bookingData();

        // Assert
        expect($data->titles)->toBeEmpty()
            ->and($data->stipulation)->toBeNull();
    });
});
