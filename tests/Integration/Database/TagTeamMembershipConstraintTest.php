<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('the current tag team membership index exists', function () {
    expect(currentTagTeamMembershipIndexExists())->toBeTrue();
});

test('a wrestler may have multiple ended tag team memberships', function () {
    $wrestler = Wrestler::factory()->create();

    TagTeam::factory()->count(2)->create()->each(
        fn (TagTeam $tagTeam) => joinTagTeam($tagTeam, $wrestler, now()->subDay())
    );

    expect(TagTeamWrestler::query()->where('wrestler_id', $wrestler->id)->count())->toBe(2);
});

test('a wrestler may have an ended membership and one current membership', function () {
    $wrestler = Wrestler::factory()->create();
    [$formerTagTeam, $currentTagTeam] = TagTeam::factory()->count(2)->create()->all();

    joinTagTeam($formerTagTeam, $wrestler, now()->subDay());
    joinTagTeam($currentTagTeam, $wrestler);

    expect(TagTeamWrestler::query()->current()->where('wrestler_id', $wrestler->id)->count())->toBe(1);
});

test('a wrestler cannot have multiple current tag team memberships', function () {
    $wrestler = Wrestler::factory()->create();
    [$firstTagTeam, $secondTagTeam] = TagTeam::factory()->count(2)->create()->all();
    joinTagTeam($firstTagTeam, $wrestler);

    // The nested transaction becomes a savepoint, so PostgreSQL keeps the surrounding
    // test transaction usable after the unique violation.
    expect(fn () => DB::transaction(fn () => joinTagTeam($secondTagTeam, $wrestler)))
        ->toThrow(QueryException::class)
        ->and(TagTeamWrestler::query()->current()->where('wrestler_id', $wrestler->id)->count())->toBe(1);
});
