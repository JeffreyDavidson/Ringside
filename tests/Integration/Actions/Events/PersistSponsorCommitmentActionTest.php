<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/sponsor-commitment.php';

use App\Actions\Events\PersistSponsorCommitmentAction;
use Illuminate\Support\Facades\DB;

test('a failed owning transaction rolls back the promise version and its private revision together', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];

    // Act
    $operation = fn () => DB::transaction(function () use ($promise): void {
        $promise->description = 'Changed banner';
        app(PersistSponsorCommitmentAction::class)->handle($promise, 'save', 'Correct position', signedInUser());

        throw new RuntimeException('Reject the complete operation');
    });

    // Assert
    expect($operation)->toThrow(RuntimeException::class)
        ->and($promise->refresh()->version)->toBe(1)
        ->and($promise->description)->toBe('Entrance banner')
        ->and($promise->revisions()->count())->toBe(1);
});

test('a promise revision records the actor passed in, not the signed-in user', function (): void {
    // Arrange
    $world = sponsorFixture();
    $promise = $world['promise'];
    $actor = administrator();

    // Act
    app(PersistSponsorCommitmentAction::class)->handle($promise, 'save', 'Correct position', $actor);

    // Assert
    $revision = $promise->revisions()->orderByDesc('id')->firstOrFail();
    expect($revision->actor_id)->toBe($actor->id)
        ->and($revision->actor_name)->toBe("{$actor->first_name} {$actor->last_name}");
});
