<?php

declare(strict_types=1);
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Promotions\Promotion;
use App\Services\Events\PublicEventSourceService;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Support\Facades\Gate;

test('missing promotion ownership cannot release even a fresh version', function (string $condition): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);

    if ($condition === 'unowned') {
        $event->forceFill(['promotion_id' => null])->save();
    }

    if ($condition === 'mismatched') {
        $publication->forceFill(['promotion_id' => Promotion::factory()->create()->id])->save();
    }
    $source = app(PublicEventSourceService::class)->get($event);

    expect(fn () => app(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Card))
        ->toThrow(EventPublicationException::class)
        ->and(freshModel($publication)->published_at)->toBeNull();
})->with(['unowned', 'mismatched']);

test('ordinary users without promotion context cannot use any publication ability', function (): void {
    $world = publicEventFixture();
    $actor = basicUser();
    app(PromotionContextService::class)->clear();

    foreach (['preparePublication', 'publishPublication', 'unpublishPublication', 'uploadPublicationPoster'] as $ability) {
        expect(Gate::forUser($actor)->allows($ability, $world['event']))->toBeFalse();
    }
});
