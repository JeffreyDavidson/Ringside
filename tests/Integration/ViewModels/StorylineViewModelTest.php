<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveStepAction;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\ViewModels\StorylineViewModel;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('next planned follows current event or standalone dates before undated sequence', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    resolve(SaveStepAction::class)->handle($story, 1, new StepData('Undated', 'Wait for an event.', 1, new SourceData, (string) Str::uuid()));
    $next = resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, new StepData('Payoff', 'The intended match.', 2, new SourceData(targetOn: today()->addDays(2)->toDateString()), (string) Str::uuid()));
    // Act
    $summary = resolve(StorylineViewModel::class)->summary($story);
    // Assert
    expect($summary['next']?->id)->toBe($next->id)->and($summary['last'])->toBeNull();
});

test('next planned compares event instants and standalone days in their actual timezones', function () {
    actingAs(administrator());
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $story = Storyline::factory()->for($promotion)->create();
    $event = Event::factory()->for($promotion)->create(['date' => '2026-11-14T03:00:00+00:00']);
    $standalone = resolve(SaveStepAction::class)->handle($story, 1, new StepData('Local next day', 'The later payoff.', 1, new SourceData(targetOn: '2026-11-14'), (string) Str::uuid()));
    $earlier = resolve(SaveStepAction::class)->handle($story, $story->refresh()->version, new StepData('Late-night event', 'The first confrontation.', 2, new SourceData($event->id), (string) Str::uuid()));
    $summary = resolve(StorylineViewModel::class)->summary($story);
    expect($summary['next']?->id)->toBe($earlier->id);
});
