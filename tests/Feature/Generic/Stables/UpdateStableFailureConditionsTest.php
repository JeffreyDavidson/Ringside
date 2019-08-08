<?php

namespace Tests\Feature\Generic\Stables;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group generics
 */
class UpdateStableFailureConditionsTest extends TestCase
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

        return array_replace_recursive([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagteam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function a_stable_name_must_be_filled()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'name' => ''
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_name_must_be_unique()
    {
        $this->actAs('administrator');
        factory(Stable::class)->create(['name' => 'Example Stable Name']);
        $stableB = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stableB))
                        ->put(route('roster.stables.update', $stableB), $this->validParams([
                            'name' => 'Example Stable Name'
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stableB));
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_started_at_date_is_required_if_they_have_been_introduced_before_the_current_day()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'started_at' => today()->toDateString()
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'started_at' => 'not-a-datetime'
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function wrestlers_are_required_if_there_is_only_one_tag_team_included()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagteam->getKey()],
                            'wrestlers' => null,
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function wrestlers_must_be_an_array()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => 'not-an-array',
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function each_wrestler_must_be_an_integer()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => ['not-an-integer'],
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function each_wrestler_must_exist()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => [99],
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_hired_in_the_pending_introduction_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_pending_introduction_wrestler_cannot_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => [$wrestler->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs('administrator');
        $otherStable = factory(Stable::class)->states('bookable')->create();
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'wrestlers' => [$otherStable->currentWrestlers->first()->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function tag_teams_must_be_an_array()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => 'not-an-array',
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function tag_teams_are_required_if_there_is_only_two_wrestlers_included()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => null,
                            'wrestlers' => $wrestlers->modelKeys(),
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function each_tag_team_must_be_an_integer()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => ['not-an-integer'],
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function each_tag_team_must_exist()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => [99],
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_hired_in_the_pending_introduction_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagteam->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_must_be_active_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => [$tagteam->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs('administrator');
        $otherStable = factory(Stable::class)->states('bookable')->create();
        $stable = factory(Stable::class)->states('bookable')->create();

        $response = $this->from(route('roster.stables.edit', $stable))
                        ->put(route('roster.stables.update', $stable), $this->validParams([
                            'tagteams' => [$otherStable->currentTagTeams->first()->getKey()]
                        ]));

        $response->assertRedirect(route('roster.stables.edit', $stable));
        $response->assertSessionHasErrors('tagteams.*');
    }
}
