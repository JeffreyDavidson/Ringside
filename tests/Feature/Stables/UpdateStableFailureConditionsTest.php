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
class UpdateStableFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        $wrestlers = WrestlerFactory::new()->count(1)->bookable()->create();
        $tagTeams = TagTeamFactory::new()->count(1)->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
            'tagteams' => $overrides['tagteams'] ?? $tagTeams->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->create();

        $response = $this->editRequest($stable);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = StableFactory::new()->create();

        $response = $this->updateRequest($stable, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = StableFactory::new()->create();

        $response = $this->editRequest($stable);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = StableFactory::new()->create();

        $response = $this->updateRequest($stable, $this->validParams());

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_stable_name_must_be_filled()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'name' => ''
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_name_must_be_unique()
    {
        $this->actAs(Role::ADMINISTRATOR);
        factory(Stable::class)->create(['name' => 'Example Stable Name']);
        $stableB = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stableB))
                        ->put(route('stables.update', $stableB), $this->validParams([
                            'name' => 'Example Stable Name'
                        ]));

        $response->assertRedirect(route('stables.edit', $stableB));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_started_at_date_is_required_if_they_have_been_introduced_before_the_current_day()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'started_at' => today()->toDateString()
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_a_datetime_format()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'started_at' => 'not-a-datetime'
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function wrestlers_are_required_if_there_is_only_one_tag_team_included()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $tagTeam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagTeam->getKey()],
                            'wrestlers' => null,
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function wrestlers_must_be_an_array()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => 'not-an-array',
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function each_wrestler_must_be_an_integer()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => ['not-an-integer'],
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function each_wrestler_must_exist()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => [99],
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_hired_in_the_pending_introduction_to_be_added_to_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_pending_introduction_wrestler_cannot_be_added_to_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $otherStable = factory(Stable::class)->states('bookable')->create();
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'wrestlers' => [$otherStable->currentWrestlers->first()->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function tag_teams_must_be_an_array()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => 'not-an-array',
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function tag_teams_are_required_if_there_is_only_two_wrestlers_included()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => null,
                            'wrestlers' => $wrestlers->modelKeys(),
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function each_tag_team_must_be_an_integer()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => ['not-an-integer'],
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function each_tag_team_must_exist()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => [99],
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_hired_in_the_pending_introduction_to_be_added_to_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $tagTeam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagTeam->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_must_be_active_to_be_added_to_a_stable()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $stable = factory(Stable::class)->create();
        $tagTeam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagTeam->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $otherStable = factory(Stable::class)->states('bookable')->create();
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('stables.edit', $stable))
                        ->put(route('stables.update', $stable), $this->validParams([
                            'tagteams' => [$otherStable->currentTagTeams->first()->getKey()]
                        ]));

        $response->assertRedirect(route('stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }
}
