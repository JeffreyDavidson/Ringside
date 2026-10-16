<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Actions\Events\SaveEventPackAction;
use App\Data\Events\EventPackData;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

describe('live show source requirements', function (): void {
    it('returns the source of a card that is ready to run', function (): void {
        // Arrange
        $fixture = liveShowFixture();

        // Act
        $source = resolve(LiveShowSourceRequirements::class)->ensureSource($fixture['event']);

        // Assert
        expect($source['pack']->version)->toBe(1)
            ->and($source['content']['event_name'])->toBe('Harbor Showcase')
            ->and($source['content']['items'])->toHaveCount(1);
    });

    it('requires the show to be prepared when the event has no pack', function (): void {
        // Arrange
        actingAs(administrator());
        $event = Event::factory()->create(['date' => now()]);
        EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create();

        // Act
        $source = fn (): array => resolve(LiveShowSourceRequirements::class)->ensureSource($event);

        // Assert
        expect($source)->toThrow(LiveShowException::class, __('live-show.prepare_required'));
    });

    it('requires the show to be prepared when the event has no date', function (): void {
        // Arrange
        $fixture = liveShowFixture();
        $fixture['event']->forceFill(['date' => null])->save();

        // Act
        $source = fn (): array => resolve(LiveShowSourceRequirements::class)->ensureSource($fixture['event']);

        // Assert
        expect($source)->toThrow(LiveShowException::class, __('live-show.prepare_required'));
    });

    it('requires the pack to be reviewed after the card changed', function (): void {
        // Arrange
        $fixture = liveShowFixture();
        EventMatch::query()->where('event_id', $fixture['event']->id)->firstOrFail()->delete();

        // Act
        $source = fn (): array => resolve(LiveShowSourceRequirements::class)->ensureSource($fixture['event']);

        // Assert
        expect($source)->toThrow(LiveShowException::class, __('live-show.prepare_required'));
    });

    it('reports a run as unchanged while its pack and card are current', function (): void {
        // Arrange
        $fixture = liveShowFixture();

        // Act
        $changed = resolve(LiveShowSourceRequirements::class)->hasChanged($fixture['event'], $fixture['run']);

        // Assert
        expect($changed)->toBeFalse();
    });

    it('reports a run as changed after a newer pack version or a changed card', function (string $change): void {
        // Arrange
        $fixture = liveShowFixture();
        if ($change === 'pack') {
            $pack = $fixture['event']->pack()->firstOrFail();
            resolve(SaveEventPackAction::class)->handle($fixture['event'], 1, $pack->card_fingerprint, EventPackData::fromValidated([...$pack->content, 'notes' => 'Revised']), false);
        }
        if ($change === 'card') {
            EventMatch::query()->where('event_id', $fixture['event']->id)->firstOrFail()->delete();
        }

        // Act
        $changed = resolve(LiveShowSourceRequirements::class)->hasChanged($fixture['event'], $fixture['run']);

        // Assert
        expect($changed)->toBeTrue();
    })->with(['pack', 'card']);
});
