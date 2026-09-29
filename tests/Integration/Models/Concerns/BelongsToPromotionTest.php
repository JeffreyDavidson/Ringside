<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Eloquent\Model;

function createPromotionOwnedRecord(string $modelClass, ?Promotion $promotion): Model
{
    $factory = match ($modelClass) {
        Wrestler::class => Wrestler::factory(),
        Manager::class => Manager::factory(),
        Referee::class => Referee::factory(),
        TagTeam::class => TagTeam::factory(),
        Stable::class => Stable::factory(),
        Title::class => Title::factory(),
        Event::class => Event::factory(),
        default => throw new InvalidArgumentException("Unsupported promotion-owned model: {$modelClass}"),
    };

    return $promotion instanceof Promotion
        ? $factory->for($promotion, 'promotion')->createOne()
        : $factory->createOne();
}

afterEach(function () {
    app(PromotionContextService::class)->clear();
});

it('returns no records when a promotion context is enforced without an active promotion', function (string $modelClass) {
    $promotion = Promotion::factory()->create();
    createPromotionOwnedRecord($modelClass, $promotion);
    createPromotionOwnedRecord($modelClass, null);
    $context = app(PromotionContextService::class);
    $context->enforce();

    $records = $modelClass::query()->get();

    expect($records)->toBeEmpty();
})->with([
    'wrestler' => Wrestler::class,
    'manager' => Manager::class,
    'referee' => Referee::class,
    'tag team' => TagTeam::class,
    'stable' => Stable::class,
    'title' => Title::class,
    'event' => Event::class,
]);

it('only returns records of the active promotion when a promotion context is enforced', function (string $modelClass) {
    $promotion = Promotion::factory()->create();
    $ownedRecord = createPromotionOwnedRecord($modelClass, $promotion);
    createPromotionOwnedRecord($modelClass, Promotion::factory()->create());
    createPromotionOwnedRecord($modelClass, null);
    $context = app(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    $records = $modelClass::query()->get();

    expect($records)->toHaveCount(1)
        ->and($records->first()?->is($ownedRecord))->toBeTrue();
})->with([
    'wrestler' => Wrestler::class,
    'manager' => Manager::class,
    'referee' => Referee::class,
    'tag team' => TagTeam::class,
    'stable' => Stable::class,
    'title' => Title::class,
    'event' => Event::class,
]);

it('returns every record when no promotion context is enforced', function (string $modelClass) {
    createPromotionOwnedRecord($modelClass, Promotion::factory()->create());
    createPromotionOwnedRecord($modelClass, null);

    $records = $modelClass::query()->get();

    expect($records)->toHaveCount(2);
})->with([
    'wrestler' => Wrestler::class,
    'manager' => Manager::class,
    'referee' => Referee::class,
    'tag team' => TagTeam::class,
    'stable' => Stable::class,
    'title' => Title::class,
    'event' => Event::class,
]);

it('refuses to create records when a promotion context is enforced without an active promotion', function (string $modelClass) {
    $context = app(PromotionContextService::class);
    $context->enforce();

    $create = fn (): Model => createPromotionOwnedRecord($modelClass, null);

    expect($create)->toThrow(LogicException::class, 'No active promotion context has been established.');
})->with([
    'wrestler' => Wrestler::class,
    'title' => Title::class,
]);
