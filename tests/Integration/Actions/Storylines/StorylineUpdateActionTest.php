<?php

declare(strict_types=1);
use App\Actions\Storylines\UpdateAction;
use App\Data\Storylines\StorylineData;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('editing a premise retains the old creative account privately', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create(['premise' => 'Former partners.', 'payoff' => 'Secret title match.']);
    // Act
    resolve(UpdateAction::class)->handle($story, 1, new StorylineData('Reconciliation', 'They reconcile.', 'Secret title match.', (string) Str::uuid()), 'A new direction.', signedInUser());
    // Assert
    expect($story->refresh()->premise)->toBe('They reconcile.')
        ->and($story->revisions()->firstOrFail()->before['premise'])->toBe('Former partners.');
});

test('editing a premise records the actor passed in', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();

    // Act
    resolve(UpdateAction::class)->handle($story, 1, new StorylineData('Reconciliation', 'They reconcile.', null, (string) Str::uuid()), 'A new direction.', $actor);

    // Assert
    expect($story->revisions()->sole()->actor_id)->toBe($actor->id);
});
