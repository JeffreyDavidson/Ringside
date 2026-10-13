<?php

declare(strict_types=1);
use App\Enums\Roster\BookableRosterKind;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Storylines\CreativeRosterSearch;

use function Pest\Laravel\actingAs;

test('creative search includes unavailable talent but excludes foreign and deleted records and caps results', function () {
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $own = Wrestler::factory()->for($promotion)->create(['name' => 'Harbor Rival']);
    Wrestler::factory()->for(Promotion::factory())->create(['name' => 'Harbor Foreign']);
    $deleted = Wrestler::factory()->for($promotion)->create(['name' => 'Harbor Deleted']);
    $deleted->delete();
    $search = resolve(CreativeRosterSearch::class);
    $results = $search->search(BookableRosterKind::Wrestlers, 'Harbor', $promotion->id);
    expect(array_column($results, 'id'))->toBe([$own->id]);
    Wrestler::factory()->count(25)->for($promotion)->create();
    expect($search->search(BookableRosterKind::Wrestlers, '%_\\', $promotion->id))->toHaveCount(20);
});
test('creative teams do not need to be bookable', function () {
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $team = TagTeam::factory()->for($promotion)->create(['name' => 'Creative Crew']);
    $results = resolve(CreativeRosterSearch::class)->search(BookableRosterKind::TagTeams, 'Crew', $promotion->id);
    expect(array_column($results, 'id'))->toBe([$team->id])
        ->and(fn () => resolve(CreativeRosterSearch::class)->search(BookableRosterKind::Referees, '', $promotion->id))->toThrow(InvalidArgumentException::class);
});
