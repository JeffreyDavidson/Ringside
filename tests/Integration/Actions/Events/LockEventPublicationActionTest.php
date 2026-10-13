<?php

declare(strict_types=1);
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Users\UserStatus;
use App\Exceptions\Events\EventPublicationException;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

test('actions recheck changed membership instead of trusting a previously opened form', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    auth()->logout();
    session()->flush();
    $promotion = $event->promotion()->firstOrFail();
    $actor = actingAsPromotionMember($promotion, MembershipRole::Manager);
    $source = app(PublicEventSourceQuery::class)->get($event);
    changePromotionMembership($promotion, $actor, MembershipRole::Member);

    expect(fn () => app(UnpublishEventPublicationAction::class)->handle($event, $actor, $publication->version, $source['fingerprint'], PublicSection::Card))->toThrow(AuthorizationException::class)
        ->and(freshModel($publication)->published_at)->not->toBeNull();
});

test('deleted actors and missing promotion ownership cannot release even a fresh version', function (string $condition): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    if ($condition === 'actor') {
        $world['actor']->update(['status' => UserStatus::Inactive]);
    }
    if ($condition === 'unowned') {
        $event->forceFill(['promotion_id' => null])->save();
    }
    if ($condition === 'mismatched') {
        $publication->forceFill(['promotion_id' => Promotion::factory()->create()->id])->save();
    }
    $source = app(PublicEventSourceQuery::class)->get($event);

    expect(fn () => app(ReleaseEventPublicationAction::class)->handle($event, $world['actor'], $publication->version, $source['fingerprint'], PublicSection::Card))
        ->toThrow($condition === 'actor' ? AuthorizationException::class : EventPublicationException::class)
        ->and(freshModel($publication)->published_at)->toBeNull();
})->with(['actor', 'unowned', 'mismatched']);

test('ordinary users without promotion context cannot use any publication ability', function (): void {
    $world = publicEventFixture();
    $actor = basicUser();
    app(PromotionContextService::class)->clear();

    foreach (['preparePublication', 'publishPublication', 'unpublishPublication', 'uploadPublicationPoster'] as $ability) {
        expect(Gate::forUser($actor)->allows($ability, $world['event']))->toBeFalse();
    }
});
