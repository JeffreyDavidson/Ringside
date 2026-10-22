<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveStepAction;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\RecordChangedException;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Events\Event;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a planned event step preserves its original target and rejects a stale save', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $event = Event::factory()->for(requiredModel($story->promotion))->scheduled()->create();
    $data = new StepData('The betrayal', 'Turn on the partner after the bell.', 1, new SourceData($event->id), (string) Str::uuid());

    // Act
    $step = resolve(SaveStepAction::class)->handle($story, $story->version, $data);

    // Assert
    expect($step->status)->toBe(StepStatus::Planned)
        ->and($step->source_context['event_date'])->toBe(requiredDate($event->date)->toIso8601String())
        ->and($step->target_on)->toBeNull();
    expect(fn () => resolve(SaveStepAction::class)->handle($story, $story->version, $data))->toThrow(RecordChangedException::class)
        ->and($story->steps()->count())->toBe(1);
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('revising a plan retains the first description and rejects changes to confirmed plans', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $revised = new StepData('New plan', 'A confrontation instead.', 3, new SourceData(targetOn: today()->addDays(3)->toDateString()), (string) Str::uuid(), 'Talent changed.');
    resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, $revised, $step->id);
    expect($story->revisions()->orderByDesc('id')->firstOrFail()->before['description'])->toBe('Attack the partner.');
    confirmedStorylineDevelopment($story, $step->id);
    expect(fn () => resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, $revised, $step->id))->toThrow(StorylineConflictException::class);
});
test('a reused step submission identity is refused even with a fresh story version', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $data = new StepData('Repeated plan', 'Same submission.', 1, new SourceData, $step->submission_id);
    expect(fn () => resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, $data))->toThrow(RecordChangedException::class);
});

test('planning a step records the actor passed in', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();

    // Act
    resolve(SaveStepAction::class)->handle($story, 1, new StepData('The betrayal', 'Turn on the partner.', 1, new SourceData, (string) Str::uuid()), $actor);

    // Assert
    expect($story->revisions()->sole()->actor_id)->toBe($actor->id);
});
