<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Services\Matches\EventMatchDeletionService;

test('it soft deletes a match while preserving its assignments', function (): void {
    $eventMatch = EventMatch::factory()->complete()->withReferees()->create();
    $competitorIds = $eventMatch->competitors()->pluck('id');
    $refereeIds = $eventMatch->referees()->pluck('referees.id');

    resolve(EventMatchDeletionService::class)->delete($eventMatch, now());

    expect(EventMatch::query()->find($eventMatch->id))->toBeNull()
        ->and(EventMatch::withTrashed()->find($eventMatch->id))->not->toBeNull()
        ->and($eventMatch->competitors()->pluck('id'))->toEqual($competitorIds)
        ->and($eventMatch->referees()->pluck('referees.id'))->toEqual($refereeIds);
});
