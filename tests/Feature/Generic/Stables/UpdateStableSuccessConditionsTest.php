<?php

namespace Tests\Feature\Generic\Stables;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class UpdateStableSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? [$wrestler->getKey()],
            'tagteams' => $overrides['tagteams'] ?? [$tagteam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function wrestlers_can_rejoin_a_stable()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable->wrestlerHistory()->attach($wrestler->getKey(), ['left_at' => now()]);

        $this->from(route('roster.stables.edit', $stable))
            ->put(route('roster.stables.update', [$stable]), [
                'wrestlers' => [$wrestler->getKey()]
            ]);

        tap($stable->fresh()->currentWrestlers, function ($wrestlers) use ($wrestler) {
            $this->assertCount(1, $wrestlers);
            $this->assertEquals($wrestlers->first()->id, $wrestler->id);
        });
    }

    /** @test */
    public function wrestlers_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $newStableWrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
            ->put(route('roster.stables.update', $stable), $this->validParams([
                'wrestlers' => $newStableWrestlers->modelKeys(),
            ]));

        tap($stable->fresh()->currentWrestlers, function ($currentStableWrestlers) use ($newStableWrestlers) {
            $this->assertCount(2, $currentStableWrestlers);
            $this->assertEquals($currentStableWrestlers->modelKeys(), $newStableWrestlers->modelKeys());
        });
    }

    /** @test */
    public function wrestlers_in_a_stable_that_are_not_included_request_are_marked_as_left()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();
        $formerWrestlers = $stable->currentWrestlers;

        $this->assertCount(1, $formerWrestlers);

        $newStableWrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $this->from(route('roster.stables.edit', $stable))
            ->put(route('roster.stables.update', $stable), $this->validParams([
                'wrestlers' => $newStableWrestlers->modelKeys(),
            ]));

        tap($stable->fresh()->previousWrestlers, function ($stableWrestlers) use ($formerWrestlers) {
            $this->assertCount(1, $stableWrestlers);
            $this->assertEquals($stableWrestlers->modelKeys(), $formerWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_in_a_stable_that_are_not_included_request_are_marked_as_left()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('pending-introduction')->create();
        $formerTagTeams = $stable->currentTagTeams;
        $tagteams = factory(TagTeam::class, 2)->states('bookable')->create();

        $this->from(route('roster.stables.edit', $stable))
            ->put(route('roster.stables.update', $stable), $this->validParams([
                'tagteams' => $tagteams->modelKeys(),
            ]));

        tap($stable->fresh()->previousTagTeams, function ($stableTagTeams) use ($formerTagTeams) {
            $this->assertCount(1, $formerTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $formerTagTeams->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteams = factory(TagTeam::class, 2)->states('bookable')->create();

        $this->from(route('roster.stables.edit', $stable))
            ->put(route('roster.stables.update', $stable), $this->validParams([
                'tagteams' => $tagteams->modelKeys(),
            ]));

        tap($stable->fresh()->currentTagTeams, function ($stableTagTeams) use ($tagteams) {
            $this->assertCount(2, $stableTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $tagteams->modelKeys());
        });
    }
}
