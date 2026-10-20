<?php

declare(strict_types=1);
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Actions\Storylines\SaveParticipantAction;
use App\Actions\Storylines\SaveStepAction;
use App\Actions\Storylines\TransitionStepAction;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\ParticipantData;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('confirming a different outcome retains the original plan and cannot be replayed', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = resolve(SaveStepAction::class)->handle($story, 1, new StepData('Betrayal', 'Attack the partner.', 1, new SourceData, (string) Str::uuid()));
    $data = new DevelopmentData('A warning', 'They argued but stayed together.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid(), $step->id);
    $story->refresh();
    $version = $story->version;

    // Act
    $development = resolve(RecordDevelopmentAction::class)->handle($story, $version, $data);

    // Assert
    expect($development->description)->toBe('They argued but stayed together.')
        ->and($step->refresh()->description)->toBe('Attack the partner.')
        ->and($step->status)->toBe(StepStatus::Happened);
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $version, $data))->toThrow(RecordChangedException::class)
        ->and($story->developments()->count())->toBe(1);
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('a cancelled step cannot be confirmed until explicitly reopened', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Cancelled, 'Cancelled.');
    expect(fn () => confirmedStorylineDevelopment($story, $step->id))->toThrow(StorylineConflictException::class);
});
test('corrections preserve cast labels after a roster rename and keep both accounts', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Original name']);
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, 'Rival', today()->subDays(2)->toDateString()));
    $data = new DevelopmentData('Confrontation', 'An argument.', today()->toDateString(), 1, [$person->id], new SourceData, (string) Str::uuid());
    $development = resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);
    $wrestler->name = 'New name';
    $wrestler->save();
    $corrected = new DevelopmentData('Confrontation corrected', 'An argument after the match.', $data->occurredOn, 2, [$person->id], new SourceData, (string) Str::uuid(), reason: 'More accurate wording.');
    resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $corrected, $development->id);
    expect($development->refresh()->cast[0]['label'])->toBe('Original name')
        ->and($story->revisions()->orderByDesc('id')->firstOrFail()->before['description'])->toBe('An argument.');
});
test('a development refuses an unknown or not-yet-involved cast member', function (string $kind) {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $id = 99999;
    if ($kind !== 'unknown') {
        $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
        $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->addDay()->toDateString()));
        $id = $person->id;
    }
    $data = new DevelopmentData('Confrontation', 'An argument.', today()->toDateString(), 1, [$id], new SourceData, (string) Str::uuid());
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(StorylineConflictException::class)
        ->and($story->developments()->count())->toBe(0);
})->with(['unknown', 'future participant']);
test('a missing roster identity cannot silently become a confirmed performer', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()));
    $wrestler->forceDelete();
    $data = new DevelopmentData('Confrontation', 'An argument.', today()->toDateString(), 1, [$person->id], new SourceData, (string) Str::uuid());
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(StorylineConflictException::class);
});
test('an account correction cannot be reassigned to a different planned step', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $account = confirmedStorylineDevelopment($story, $step->id);
    $data = new DevelopmentData('Correction', 'Different wording.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid(), reason: 'Correction.');
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data, $account->id))->toThrow(StorylineConflictException::class);
});
test('unplanned submissions cannot be duplicated with a fresh version', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $data = new DevelopmentData('Confrontation', 'An argument.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid());
    resolve(RecordDevelopmentAction::class)->handle($story, 1, $data);
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(RecordChangedException::class);
});

test('a later promotion timezone change cannot move the date of an existing account correction', function () {
    actingAs(administrator());
    Carbon::setTestNow('2026-10-14T03:00:00+00:00');
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $story = Storyline::factory()->for($promotion)->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()]);
    $source = new SourceData($event->id);
    $data = new DevelopmentData('Night promo', 'A confrontation.', '2026-10-13', 1, [], $source, (string) Str::uuid());
    $development = resolve(RecordDevelopmentAction::class)->handle($story, 1, $data);
    $promotion->timezone = 'UTC';
    $promotion->save();
    $corrected = new DevelopmentData('Night promo', 'A clarified confrontation.', '2026-10-13', 1, [], $source, (string) Str::uuid(), reason: 'Correcting wording; retain the recorded date.');
    resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $corrected, $development->id);
    expect($development->refresh()->occurred_on->toDateString())->toBe('2026-10-13')->and($development->timezone)->toBe('America/New_York');
});

test('recording an unchanged linked source ignores saved JSON object key order', function () {
    // Arrange
    $fixture = storylineSourceFixture();
    $story = $fixture['story'];
    $source = new SourceData(eventId: $fixture['event']->id, matchId: $fixture['match']->id);
    $step = plannedStorylineStep($story, $source);
    $step->source_context = array_reverse($step->source_context, preserve_keys: true);
    $step->save();
    $data = new DevelopmentData('Actual confrontation', 'The planned confrontation happened.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id);

    // Act
    $development = resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect($development->storyline_step_id)->toBe($step->id)
        ->and($step->refresh()->status)->toBe(StepStatus::Happened)
        ->and($story->developments()->count())->toBe(1);
});

test('source context comparison retains strict scalar types and ordered lists', function (string $change) {
    // Arrange
    $fixture = storylineSourceFixture();
    $story = $fixture['story'];
    $source = new SourceData(eventId: $fixture['event']->id, matchId: $fixture['match']->id);
    $step = plannedStorylineStep($story, $source);
    $context = $step->source_context;
    if ($change === 'scalar type') {
        $context['event_deleted'] = 0;
    }
    if ($change === 'list order') {
        $competitors = $context['competitors'];
        if (! is_array($competitors)) {
            throw new LogicException('The linked match fixture must contain a competitor list.');
        }
        $context['competitors'] = array_reverse($competitors);
    }
    $step->source_context = $context;
    $step->save();
    $data = new DevelopmentData('Actual confrontation', 'The confrontation happened.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id);

    // Act / Assert
    expect(fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(StorylineConflictException::class)
        ->and($story->developments()->count())->toBe(0)
        ->and($step->refresh()->status)->toBe(StepStatus::Planned);
})->with(['scalar type', 'list order']);

test('a step that is no longer planned is refused before a missing reason is asked for', function () {
    // Arrange
    $fixture = storylineSourceFixture();
    $story = $fixture['story'];
    $fixture['event']->date = null;
    $fixture['event']->save();
    $source = new SourceData($fixture['event']->id);
    $step = plannedStorylineStep($story, $source);
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Cancelled, 'Cancelled.');
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [], $source, (string) Str::uuid(), $step->id);

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
});

test('a cast that cannot take part is refused before a correction that names another step', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $account = confirmedStorylineDevelopment($story, $step->id);
    $data = new DevelopmentData('Correction', 'Different wording.', today()->toDateString(), 1, [99999], new SourceData, (string) Str::uuid(), reason: 'Correction.');

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data, $account->id);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
});

test('a correction that names another step is refused as a state conflict', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $account = confirmedStorylineDevelopment($story, $step->id);
    $data = new DevelopmentData('Correction', 'Different wording.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid(), reason: 'Correction.');

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data, $account->id);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
});

test('replaying a submission whose cast no longer fits is a source conflict, not a record change', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $submission = (string) Str::uuid();
    resolve(RecordDevelopmentAction::class)->handle($story, 1, new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [], new SourceData, $submission));
    $replay = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [99999], new SourceData, $submission);

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $replay);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'));
});

test('a development writes each cast member as type, id and label in that order', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create(['name' => 'Harbor Hawk']);
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->toDateString()));
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [$person->id], new SourceData, (string) Str::uuid());

    // Act
    $development = resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect(array_keys($development->cast[0]))->toBe(['type', 'id', 'label'])
        ->and($development->refresh()->cast[0])->toEqual(['type' => 'wrestler', 'id' => $wrestler->id, 'label' => 'Harbor Hawk']);
});

test('an account is refused when its date is not the local date of the linked event', function () {
    // Arrange
    $fixture = storylineSourceFixture();
    $story = $fixture['story'];
    $data = new DevelopmentData('Angle', 'It happened.', today()->subDay()->toDateString(), 1, [], new SourceData($fixture['event']->id), (string) Str::uuid());

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'))
        ->and($story->developments()->count())->toBe(0);
});

test('an account is refused when its event has not happened yet', function () {
    // Arrange
    $fixture = storylineSourceFixture();
    $story = $fixture['story'];
    $fixture['event']->date = now()->addDay();
    $fixture['event']->save();
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [], new SourceData($fixture['event']->id), (string) Str::uuid());

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'))
        ->and($story->developments()->count())->toBe(0);
});

test('a participant who left before the account date cannot be in its cast', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $wrestler = Wrestler::factory()->for(requiredModel($story->promotion))->create();
    $person = resolve(SaveParticipantAction::class)->handle($story, 1, new ParticipantData('wrestler', $wrestler->id, null, today()->subDays(5)->toDateString()));
    resolve(SaveParticipantAction::class)->handle($story, $story->refresh()->version, new ParticipantData('wrestler', $wrestler->id, null, today()->subDays(5)->toDateString(), today()->subDays(2)->toDateString(), 'Left.'), $person->id);
    $data = new DevelopmentData('Angle', 'It happened.', today()->toDateString(), 1, [$person->id], new SourceData, (string) Str::uuid());

    // Act
    $attempt = fn () => resolve(RecordDevelopmentAction::class)->handle($story, $story->refresh()->version, $data);

    // Assert
    expect($attempt)->toThrow(StorylineConflictException::class, __('storylines.source_changed'))
        ->and($story->developments()->count())->toBe(0);
});
