<?php

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Artisan;

function createUnassignedPromotionRoot(string $modelClass): Event|Venue|Title
{
    return match ($modelClass) {
        Event::class => Event::factory()->create(),
        Venue::class => Venue::factory()->create(),
        Title::class => Title::factory()->create(),
        default => throw new InvalidArgumentException("Unsupported promotion root: {$modelClass}"),
    };
}

test('events, venues, and titles can be assigned to a promotion', function (string $modelClass) {
    $promotion = Promotion::factory()->create();

    $record = createUnassignedPromotionRoot($modelClass);

    expect($record->promotion_id)->toBeNull();

    $record->promotion()->associate($promotion);
    $record->save();
    $record->refresh();

    expect($record->promotion_id)->toBe($promotion->id);
})->with([
    Event::class,
    Venue::class,
    Title::class,
]);

test('event, title, and venue ownership backfill assigns only unowned records', function () {
    $promotion = Promotion::factory()->create();
    $otherPromotion = Promotion::factory()->create();

    $unownedRecords = array_map(
        createUnassignedPromotionRoot(...),
        [Event::class, Venue::class, Title::class],
    );
    $ownedTitle = Title::factory()->for($otherPromotion, 'promotion')->create();

    $exitCode = Artisan::call('promotions:backfill-event-title-ownership', [
        'promotion' => $promotion->id,
        '--force' => true,
    ]);

    $ownedTitle->refresh();

    expect($exitCode)->toBe(0)
        ->and($ownedTitle->promotion_id)->toBe($otherPromotion->id);

    foreach ($unownedRecords as $record) {
        $record->refresh();

        expect($record->promotion_id)->toBe($promotion->id);
    }
});

test('event, title, and venue ownership backfill requires confirmation unless previewing', function () {
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->create();

    $exitCode = Artisan::call('promotions:backfill-event-title-ownership', [
        'promotion' => $promotion->id,
    ]);

    $event->refresh();

    $dryRunExitCode = Artisan::call('promotions:backfill-event-title-ownership', [
        'promotion' => $promotion->id,
        '--dry-run' => true,
    ]);

    $event->refresh();

    expect($exitCode)->toBe(1)
        ->and($dryRunExitCode)->toBe(0)
        ->and($event->promotion_id)->toBeNull();
});
