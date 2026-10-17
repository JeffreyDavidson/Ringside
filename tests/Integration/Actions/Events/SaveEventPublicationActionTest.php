<?php

declare(strict_types=1);
use App\Actions\Events\SaveEventPublicationAction;
use App\Data\Events\EventPublicationDraftData;
use App\Exceptions\RecordChangedException;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;

test('first draft retries retain a single publication identity and reject stale input', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $source = app(PublicEventSourceService::class)->get($event);

    expect(fn () => app(SaveEventPublicationAction::class)->handle($event, 0, $source['fingerprint'], EventPublicationDraftData::fromValidated($publication->draft)))->toThrow(RecordChangedException::class)
        ->and(EventPublication::query()->count())->toBe(1);
});
