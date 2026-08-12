<?php

declare(strict_types=1);

use App\Enums\Lifecycle\LifecycleOwnerType;
use App\Models\Events\Event;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;

test('it resolves supported lifecycle owner models', function (LifecycleOwnerType $ownerType, Model $owner) {
    expect(LifecycleOwnerType::fromModel($owner))->toBe($ownerType)
        ->and($ownerType->morphAlias())->toBe($owner->getMorphClass());
})->with([
    'manager' => [LifecycleOwnerType::Manager, new Manager()],
    'referee' => [LifecycleOwnerType::Referee, new Referee()],
    'stable' => [LifecycleOwnerType::Stable, new Stable()],
    'tag team' => [LifecycleOwnerType::TagTeam, new TagTeam()],
    'title' => [LifecycleOwnerType::Title, new Title()],
    'wrestler' => [LifecycleOwnerType::Wrestler, new Wrestler()],
]);

test('it rejects models that cannot own lifecycle transitions', function () {
    expect(fn () => LifecycleOwnerType::fromModel(new Event()))
        ->toThrow(InvalidArgumentException::class, 'Unsupported lifecycle owner');
});
