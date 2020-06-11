<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
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
        $wrestlers = WrestlerFactory::new()->bookable()->create();
        $tagTeams = TagTeamFactory::new()->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestlers->getKey()],
            'tagteams' => [$tagTeams->getKey()],
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = StableFactory::new()->create();

        $response = $this->editRequest($stable);

        $response->assertViewIs('stables.edit');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function an_administrator_can_update_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = StableFactory::new()->create();

        $response = $this->updateRequest($stable, $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    /** @test */
    public function wrestlers_can_rejoin_a_stable()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('bookable')->create();
        $stable->wrestlerHistory()->attach($wrestler->getKey(), ['left_at' => now()]);

        $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', [$stable]), [
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
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $newStableWrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams([
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
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('pending-introduction')->create();
        $formerWrestlers = $stable->currentWrestlers;

        $this->assertCount(1, $formerWrestlers);

        $newStableWrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams([
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
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('pending-introduction')->create();
        $formerTagTeams = $stable->currentTagTeams;
        $tagTeams = factory(TagTeam::class, 2)->states('bookable')->create();

        $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams([
                'tagteams' => $tagTeams->modelKeys(),
            ]));

        tap($stable->fresh()->previousTagTeams, function ($stableTagTeams) use ($formerTagTeams) {
            $this->assertCount(1, $formerTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $formerTagTeams->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $tagTeams = factory(TagTeam::class, 2)->states('bookable')->create();

        $this->from(route('stables.edit', $stable))
            ->put(route('stables.update', $stable), $this->validParams([
                'tagteams' => $tagTeams->modelKeys(),
            ]));

        tap($stable->fresh()->currentTagTeams, function ($stableTagTeams) use ($tagTeams) {
            $this->assertCount(2, $stableTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $tagTeams->modelKeys());
        });
    }
}
