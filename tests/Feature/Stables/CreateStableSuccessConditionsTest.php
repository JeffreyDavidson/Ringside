<?php

namespace Tests\Feature\Stables;

use App\Enums\Role;
use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 */
class CreateStableSuccessConditionsTest extends TestCase
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
        $wrestler = WrestlerFactory::new()->count(1)->bookable()->create();
        $tagTeam = TagTeamFactory::new()->count(1)->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagTeam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('stable');

        $response->assertViewIs('stables.create');
    }

    /** @test */
    public function an_administrator_can_create_a_stable()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('stable', $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap(Stable::first(), function ($stable) use ($now) {
            $this->assertEquals('Example Stable Name', $stable->name);
            $this->assertEquals($now->toDateTimeString(), $stable->currentEmployment->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function wrestlers_are_added_to_stable_if_present()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $createdWrestlers = factory(Wrestler::class, 3)->states('bookable')->create();

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString(),
            'wrestlers' => $createdWrestlers->modelKeys()
        ]));

        tap(Stable::first()->currentWrestlers, function ($wrestlers) use ($createdWrestlers) {
            $this->assertCount(3, $wrestlers);
            $this->assertEquals($wrestlers->modelKeys(), $createdWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_are_added_to_stable_if_present()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $createdTagTeams = factory(TagTeam::class, 3)->states('bookable')->create();

        $this->post(route('stables.store'), $this->validParams([
            'tagteams' => $createdTagTeams->modelKeys()
        ]));

        tap(Stable::first()->currentTagTeams, function ($tagTeams) use ($createdTagTeams) {
            $this->assertCount(3, $tagTeams);
            $this->assertEquals($tagTeams->modelKeys(), $createdTagTeams->modelKeys());
        });
    }

    /** @test */
    public function a_stables_members_join_when_stable_is_started_if_filled()
    {
        $now = now()->subDays(3);
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => $now->toDateTimeString()
        ]));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagTeams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagTeams->each(function ($tagTeam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagTeam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }

    /** @test */
    public function a_stables_members_join_at_the_current_time_when_stable_is_created_if_started_at_is_not_filled()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $this->post(route('stables.store'), $this->validParams([
            'started_at' => ''
        ]));

        tap(Stable::first(), function ($stable) use ($now) {
            $wrestlers = $stable->currentWrestlers()->get();
            $tagTeams = $stable->currentTagTeams()->get();
            $wrestlers->each(function ($wrestler) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $wrestler->pivot->joined_at->toDateTimeString()
                );
            });
            $tagTeams->each(function ($tagTeam) use ($now) {
                $this->assertEquals(
                    $now->toDateTimeString(),
                    $tagTeam->pivot->joined_at->toDateTimeString()
                );
            });
        });
    }
}
