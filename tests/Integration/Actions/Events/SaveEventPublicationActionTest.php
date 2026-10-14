<?php

declare(strict_types=1);
use App\Actions\Events\SaveEventPublicationAction;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\EventPublication;
use App\Queries\Events\PublicEventSourceQuery;

test('first draft retries retain a single publication identity and reject stale input', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $source = app(PublicEventSourceQuery::class)->get($event);

    expect(fn () => app(SaveEventPublicationAction::class)->handle($event, $world['actor'], 0, $source['fingerprint'], $publication->draft))->toThrow(EventPublicationException::class)
        ->and(EventPublication::query()->count())->toBe(1);
});
