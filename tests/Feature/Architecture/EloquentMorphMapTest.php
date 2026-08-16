<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Relations\Relation;

test('it enforces stable aliases for every polymorphic model', function () {
    expect(Relation::requiresMorphMap())->toBeTrue()
        ->and(Relation::morphMap())->toBe([
            'wrestler' => Wrestler::class,
            'manager' => Manager::class,
            'match' => EventMatch::class,
            'title' => Title::class,
            'tag_team' => TagTeam::class,
            'referee' => Referee::class,
            'stable' => Stable::class,
            'event' => Event::class,
            'venue' => Venue::class,
        ]);
});

test('it rejects models without an approved polymorphic alias', function () {
    expect(fn () => (new User())->getMorphClass())
        ->toThrow(ClassMorphViolationException::class);
});
