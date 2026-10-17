<?php

declare(strict_types=1);
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;

test('releasing an unprepared draft or unpublishing it is refused without creating a row', function (string $operation): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $source = app(PublicEventSourceService::class)->get($event);
    $action = $operation === 'publish' ? app(ReleaseEventPublicationAction::class) : app(UnpublishEventPublicationAction::class);

    expect(fn () => $action->handle($event, $world['actor'], 0, $source['fingerprint'], PublicSection::Card))->toThrow(EventPublicationException::class)
        ->and(EventPublication::query()->count())->toBe(0);
})->with(['publish', 'unpublish']);
