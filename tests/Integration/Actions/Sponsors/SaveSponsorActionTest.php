<?php

declare(strict_types=1);

use App\Actions\Sponsors\SaveSponsorAction;
use App\Data\Sponsors\SponsorData;
use App\Exceptions\RecordChangedException;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Support\Str;

test('a reusable sponsor is saved with private contact details', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();

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
