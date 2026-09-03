<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleOwnerType;
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
use Illuminate\Database\Eloquent\Model;

test('it resolves supported lifecycle owner models', function (LifecycleOwnerType $ownerType, Model $owner) {
    expect(LifecycleOwnerType::fromModel($owner))->toBe($ownerType)
        ->and($ownerType->morphAlias())->toBe($owner->getMorphClass());
})->with([
    'event' => [LifecycleOwnerType::Event, new Event()],
    'manager' => [LifecycleOwnerType::Manager, new Manager()],
    'match' => [LifecycleOwnerType::Match, new EventMatch()],
    'referee' => [LifecycleOwnerType::Referee, new Referee()],
    'stable' => [LifecycleOwnerType::Stable, new Stable()],
    'tag team' => [LifecycleOwnerType::TagTeam, new TagTeam()],
    'title' => [LifecycleOwnerType::Title, new Title()],
    'venue' => [LifecycleOwnerType::Venue, new Venue()],
    'wrestler' => [LifecycleOwnerType::Wrestler, new Wrestler()],
]);

test('it rejects models that cannot own lifecycle transitions', function () {
    expect(fn () => LifecycleOwnerType::fromModel(new User()))
        ->toThrow(InvalidArgumentException::class, 'Unsupported lifecycle owner');
});
