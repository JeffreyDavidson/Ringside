<?php

declare(strict_types=1);
use App\Data\Storylines\SourceData;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

test('historical links remain readable after an event and match are deleted', function () {
    $f = storylineSourceFixture();
    $step = plannedStorylineStep($f['story'], new SourceData($f['event']->id, $f['match']->id));
    $development = StorylineDevelopment::factory()->for($f['story'])->create(['event_id' => $f['event']->id, 'match_id' => $f['match']->id]);
    $f['match']->delete();
    $f['event']->delete();
    $step->load(['storyline', 'event', 'match']);
    $development->load(['event', 'match', 'storyline']);
    expect($step->storyline->id)->toBe($f['story']->id)
        ->and($development->event?->trashed())->toBeTrue()
        ->and($development->match?->trashed())->toBeTrue()
        ->and($development->storyline->id)->toBe($f['story']->id);
});
test('the database refuses two open participations for the same creative identity', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $person = StorylineParticipant::factory()->for($story)->create();
    $person->load(['storyline', 'participant']);
    expect($person->storyline->id)->toBe($story->id)->and($person->participant)->not->toBeNull()
        ->and(requiredModel($person->participant)->getAttribute('promotion_id'))->toBe($story->promotion_id);
    $values = $person->getRawOriginal();
    unset($values['id'], $values['open_participant_id']);
    expect(fn () => DB::transaction(fn (): bool => DB::table('storyline_participants')->insert($values)))->toThrow(QueryException::class)
        ->and($story->participants()->count())->toBe(1);
});
test('story records cannot be mass assigned privileged ownership status or versions', function () {
    $story = new Storyline(['title' => 'Private', 'promotion_id' => 999, 'status' => 'completed', 'version' => 999]);
    expect($story->getAttributes())->toBe(['title' => 'Private'])
        ->and(new StorylineStep(['heading' => 'Injected', 'storyline_id' => 999, 'promotion_id' => 999])->getAttributes())->toBe(['heading' => 'Injected']);
});
require_once __DIR__.'/../../../Fixtures/storylines.php';
