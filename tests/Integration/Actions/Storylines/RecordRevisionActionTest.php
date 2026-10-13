<?php

declare(strict_types=1);
use App\Actions\Storylines\RecordRevisionAction;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineRevision;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

test('durable revisions survive general activity log retention and retain actor and account', function () {
    actingAs($actor = administrator());
    $story = Storyline::factory()->create(['payoff' => 'Private payoff.']);
    resolve(RecordRevisionAction::class)->handle($story, $story, 'story_revised', ['payoff' => 'Original payoff.'], 'A new direction.');
    StorylineRevision::query()->update(['created_at' => now()->subYears(2)]);
    Artisan::call('activitylog:clean');
    $revision = $story->revisions()->with(['actor', 'storyline'])->firstOrFail();
    expect($revision->before['payoff'])->toBe('Original payoff.')
        ->and($revision->after['payoff'])->toBe('Private payoff.')
        ->and($revision->actor?->id)->toBe($actor->id)
        ->and($revision->storyline->id)->toBe($story->id);
});
