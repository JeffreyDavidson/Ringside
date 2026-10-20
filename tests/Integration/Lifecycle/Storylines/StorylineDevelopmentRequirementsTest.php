<?php

declare(strict_types=1);

use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineSourceWarning;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Storylines\StorylineDevelopmentRequirements;
use App\Models\Events\Event;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;
use Illuminate\Support\Carbon;

/**
 * @param  array<string, mixed>  $context
 * @param  array<int, StorylineSourceWarning>  $warnings
 * @return array{context: array<string, mixed>, fingerprint: string, warnings: list<StorylineSourceWarning>}
 */
function developmentSource(array $context = ['event_id' => 1], array $warnings = []): array
{
    return ['context' => $context, 'fingerprint' => 'fingerprint', 'warnings' => array_values($warnings)];
}

describe('storyline development requirements', function (): void {
    it('confirms a planned step, a correction of any step and an unplanned account', function (?StepStatus $status, ?int $developmentId): void {
        // Arrange
        $step = $status === null ? null : StorylineStep::factory()->make(['status' => $status]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureStepCanBeConfirmed($step, $developmentId);

        // Assert
        expect($check)->not->toThrow(StorylineConflictException::class);
    })->with([
        'planned step' => [StepStatus::Planned, null],
        'no step' => [null, null],
        'correction of a happened step' => [StepStatus::Happened, 5],
        'correction of a cancelled step' => [StepStatus::Cancelled, 5],
    ]);

    it('refuses a new account for a step that is not planned', function (StepStatus $status): void {
        // Arrange
        $step = StorylineStep::factory()->make(['status' => $status]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureStepCanBeConfirmed($step, null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([StepStatus::Postponed, StepStatus::Cancelled, StepStatus::Happened]);

    it('accepts an unchanged source without a reason', function (?string $reason): void {
        // Arrange
        $step = StorylineStep::factory()->make(['source_context' => ['b' => 2, 'a' => 1]]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureSourceExplained($step, developmentSource(['a' => 1, 'b' => 2]), $reason);

        // Assert
        expect($check)->not->toThrow(StorylineConflictException::class);
    })->with([null, '', '  ']);

    it('accepts a changed or warned source with a reason', function (): void {
        // Arrange
        $step = StorylineStep::factory()->make(['source_context' => ['a' => 1]]);
        $source = developmentSource(['a' => 2], [StorylineSourceWarning::SkippedSource]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureSourceExplained($step, $source, 'The ring promo was skipped.');

        // Assert
        expect($check)->not->toThrow(StorylineConflictException::class);
    });

    it('asks for a reason when the source has a warning or no longer matches the step', function (array $saved, array $current, array $warnings, ?string $reason): void {
        // Arrange
        $step = StorylineStep::factory()->make(['source_context' => $saved]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureSourceExplained($step, developmentSource($current, $warnings), $reason);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    })->with([
        'changed value' => [['a' => 1], ['a' => 2], [], null],
        'changed value type' => [['a' => 1], ['a' => '1'], [], ''],
        'changed list order' => [['a' => [1, 2]], ['a' => [2, 1]], [], '  '],
        'warning on an unchanged source' => [['a' => 1], ['a' => 1], [StorylineSourceWarning::UndatedSource], null],
    ]);

    it('asks for a reason when an unplanned account has a warning', function (): void {
        // Arrange
        $source = developmentSource([], [StorylineSourceWarning::DeletedSource]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureSourceExplained(null, $source, null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    });

    it('accepts an event that was held on the account date in the given time zone', function (): void {
        // Arrange
        Carbon::setTestNow('2026-10-20T12:00:00+00:00');
        $event = Event::factory()->make(['date' => '2026-10-14T03:00:00+00:00']);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureEventHeldOn($event, '2026-10-13', 'America/New_York');

        // Assert
        expect($check)->not->toThrow(StorylineConflictException::class);
    });

    it('refuses an event that is undated, still to come or held on another local date', function (?string $date, string $occurredOn): void {
        // Arrange
        Carbon::setTestNow('2026-10-20T12:00:00+00:00');
        $event = Event::factory()->make(['date' => $date]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureEventHeldOn($event, $occurredOn, 'UTC');

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    })->with([
        'undated' => [null, '2026-10-20'],
        'still to come' => ['2026-10-21T12:00:00+00:00', '2026-10-21'],
        'another date' => ['2026-10-19T12:00:00+00:00', '2026-10-20'],
    ]);

    it('builds the stored cast as type, id and label, keeping a label saved earlier', function (): void {
        // Arrange
        $renamed = StorylineParticipant::factory()->create(['joined_on' => '2026-10-01'])->load('participant');
        $fresh = StorylineParticipant::factory()->create(['joined_on' => '2026-10-02'])->load('participant');
        $participants = collect([$renamed, $fresh]);
        $saved = [['type' => 'wrestler', 'id' => $renamed->participant_id, 'label' => 'Original name']];

        // Act
        $cast = resolve(StorylineDevelopmentRequirements::class)->castFor($participants, [$renamed->id, $fresh->id], '2026-10-10', $saved);

        // Assert
        expect($cast)->toBe([
            ['type' => 'wrestler', 'id' => $renamed->participant_id, 'label' => 'Original name'],
            ['type' => 'wrestler', 'id' => $fresh->participant_id, 'label' => requiredModel($fresh->participant)->name],
        ])
            ->and(array_keys($cast[0]))->toBe(['type', 'id', 'label']);
    });

    it('builds an empty cast for an account with no participants', function (): void {
        // Act
        $cast = resolve(StorylineDevelopmentRequirements::class)->castFor(collect(), [], '2026-10-10', null);

        // Assert
        expect($cast)->toBeEmpty();
    });

    it('accepts a participant on the day they joined and the day they left', function (string $joined, ?string $left): void {
        // Arrange
        $participant = StorylineParticipant::factory()->create(['joined_on' => $joined, 'left_on' => $left])->load('participant');

        // Act
        $cast = resolve(StorylineDevelopmentRequirements::class)->castFor(collect([$participant]), [$participant->id], '2026-10-10', null);

        // Assert
        expect($cast)->toHaveCount(1);
    })->with([
        'joined that day' => ['2026-10-10', null],
        'left that day' => ['2026-10-01', '2026-10-10'],
    ]);

    it('refuses a cast that is missing a requested participant', function (): void {
        // Arrange
        $participant = StorylineParticipant::factory()->create()->load('participant');

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->castFor(collect([$participant]), [$participant->id, 99999], today()->toDateString(), null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    });

    it('refuses a participant who joined after the account date', function (): void {
        // Arrange
        $participant = StorylineParticipant::factory()->create(['joined_on' => '2026-10-11'])->load('participant');

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->castFor(collect([$participant]), [$participant->id], '2026-10-10', null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    });

    it('refuses a participant who left before the account date', function (): void {
        // Arrange
        $participant = StorylineParticipant::factory()->create(['joined_on' => '2026-10-01', 'left_on' => '2026-10-09'])->load('participant');

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->castFor(collect([$participant]), [$participant->id], '2026-10-10', null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    });

    it('refuses a participant who is no longer a wrestler or tag team', function (): void {
        // Arrange
        $participant = StorylineParticipant::factory()->create(['joined_on' => '2026-10-01']);
        Wrestler::query()->whereKey($participant->participant_id)->forceDelete();
        $participant->load('participant');

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->castFor(collect([$participant]), [$participant->id], '2026-10-10', null);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
    });

    it('lets a correction stay on its step and a new account have none', function (?int $recordedStep, ?int $developmentId, ?int $stepId): void {
        // Arrange
        $development = StorylineDevelopment::factory()->make(['storyline_step_id' => $recordedStep]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureStepUnchanged($development, $developmentId, $stepId);

        // Assert
        expect($check)->not->toThrow(StorylineConflictException::class);
    })->with([
        'correction on the same step' => [7, 3, 7],
        'correction of an unplanned account' => [null, 3, null],
        'new account' => [null, null, 7],
    ]);

    it('refuses a correction that names another step', function (?int $recordedStep, ?int $stepId): void {
        // Arrange
        $development = StorylineDevelopment::factory()->make(['storyline_step_id' => $recordedStep]);

        // Act
        $check = fn () => resolve(StorylineDevelopmentRequirements::class)->ensureStepUnchanged($development, 3, $stepId);

        // Assert
        expect($check)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([
        'another step' => [7, 8],
        'step removed' => [7, null],
        'step added' => [null, 7],
    ]);
});
