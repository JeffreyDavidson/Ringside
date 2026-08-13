<?php

declare(strict_types=1);

use App\Actions\Matches\DeleteAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;

test('it soft deletes a match while preserving its historical records', function () {
    $eventMatch = EventMatch::factory()->complete()->withReferees()->create();
    $competitorIds = $eventMatch->competitors()->pluck('id');
    $refereeIds = $eventMatch->referees()->pluck('referees.id');
    $resultId = $eventMatch->result()->sole()->id;

    resolve(DeleteAction::class)->handle($eventMatch);

    $this->assertSoftDeleted($eventMatch);

    expect(EventMatch::query()->find($eventMatch->id))->toBeNull()
        ->and(EventMatch::withTrashed()->find($eventMatch->id))->not->toBeNull()
        ->and($eventMatch->competitors()->pluck('id'))->toEqual($competitorIds)
        ->and($eventMatch->referees()->pluck('referees.id'))->toEqual($refereeIds)
        ->and($eventMatch->result()->sole()->id)->toBe($resultId)
        ->and($eventMatch->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Deleted);

    expect(Referee::query()->whereKey($refereeIds)->count())->toBe($refereeIds->count());
});
