<?php

declare(strict_types=1);

use App\Actions\Sponsors\SaveSponsorAction;
use App\Data\Sponsors\SponsorData;
use App\Exceptions\RecordChangedException;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('an administrator saves a reusable sponsor with private contact details', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    actingAs(administrator());

    // Act
    $sponsor = app(SaveSponsorAction::class)->handle($promotion, null, 0, (string) Str::uuid(), new SponsorData('Harbor Hardware', 'Alex', 'alex@example.test', '555-0100', 'Private agreement note'));

    // Assert
    expect($sponsor)->toBeInstanceOf(Sponsor::class)
        ->and($sponsor->name)->toBe('Harbor Hardware')
        ->and($sponsor->contact_email)->toBe('alex@example.test')
        ->and($sponsor->promotion_id)->toBe($promotion->id)
        ->and($sponsor->version)->toBe(1);
});

test('a stale directory edit or repeated create cannot replace the saved sponsor', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    actingAs(administrator());
    $key = (string) Str::uuid();
    $action = app(SaveSponsorAction::class);
    $sponsor = $action->handle($promotion, null, 0, $key, new SponsorData('Harbor Hardware'));

    // Act
    $action->handle($promotion, $sponsor->id, 1, $key, new SponsorData('Harbor Hardware', archived: true));

    // Assert
    expect(fn () => $action->handle($promotion, $sponsor->id, 1, $key, new SponsorData('Stale name')))->toThrow(RecordChangedException::class);
    expect(fn () => $action->handle($promotion, null, 0, $key, new SponsorData('Repeated name')))->toThrow(RecordChangedException::class)
        ->and(fn () => $action->handle($promotion, null, 0, 'invalid', new SponsorData('Invalid name')))->toThrow(RecordChangedException::class)
        ->and($sponsor->refresh()->archived_at)->not->toBeNull()
        ->and($sponsor->name)->toBe('Harbor Hardware')
        ->and(Sponsor::query()->count())->toBe(1);
});

test('an administrator in an enforced promotion cannot create a sponsor for another promotion', function (): void {
    // Arrange
    $owned = Promotion::factory()->create();
    $foreign = Promotion::factory()->create();
    actingAs(administrator());
    $context = app(PromotionContextService::class);
    $context->set($owned);
    $context->enforce();

    // Act and Assert
    expect(fn () => app(SaveSponsorAction::class)->handle($foreign, null, 0, (string) Str::uuid(), new SponsorData('Foreign sponsor')))
        ->toThrow(AuthorizationException::class)
        ->and(Sponsor::query()->count())->toBe(0);
});
