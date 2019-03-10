<?php

namespace Tests\Feature\Stables;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateStableTest extends TestCase
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
        $wrestlers = factory(Wrestler::class, 1)->states('active')->create();
        $tagteams = factory(TagTeam::class, 1)->states('active')->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => today()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
            'tagteams' => $overrides['tagteams'] ?? $tagteams->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.edit', $stable));

        $response->assertViewIs('stables.edit');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.edit', $stable));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('stables.edit', $stable));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }

    /** @test */
    public function wrestlers_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $newStableWrestlers = factory(Wrestler::class, 2)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => $newStableWrestlers->modelKeys(),
        ]));

        tap($stable->fresh()->wrestlers()->whereNull('left_at')->get(), function ($currentStableWrestlers) use ( $newStableWrestlers) {
            $this->assertCount(2, $currentStableWrestlers);
            $this->assertEquals($currentStableWrestlers->modelKeys(), $newStableWrestlers->modelKeys());
        });
    }

    /** @test */
    public function wrestlers_in_a_stable_that_are_not_included_request_are_marked_as_left()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $formerWrestlers = $stable->wrestlers()->whereNull('left_at')->get();
        $newStableWrestlers = factory(Wrestler::class, 2)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => $newStableWrestlers->modelKeys(),
        ]));

        tap($stable->fresh()->wrestlers()->whereNotNull('left_at')->get(), function ($stableWrestlers) use ($formerWrestlers) {
            $this->assertCount(1, $stableWrestlers);
            $this->assertEquals($stableWrestlers->modelKeys(), $formerWrestlers->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_in_a_stable_that_are_not_included_request_are_marked_as_left()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $formerTagTeams = $stable->tagteams()->whereNull('left_at')->get();
        $tagteams = factory(TagTeam::class, 2)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => $tagteams->modelKeys(),
        ]));

        tap($stable->fresh()->tagteams()->whereNotNull('left_at')->get(), function ($stableTagTeams) use ($formerTagTeams) {
            $this->assertCount(1, $formerTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $formerTagTeams->modelKeys());
        });
    }

    /** @test */
    public function tag_teams_of_stable_are_synced_when_stable_is_updated()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteams = factory(TagTeam::class, 2)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => $tagteams->modelKeys(),
        ]));

        tap($stable->fresh()->tagteams()->whereNull('left_at')->get(), function ($stableTagTeams) use ($tagteams) {
            $this->assertCount(2, $stableTagTeams);
            $this->assertEquals($stableTagTeams->modelKeys(), $tagteams->modelKeys());
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_stable()
    {
        $this->actAs('basic-user');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_stable_name_is_required()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'name' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_stable_started_at_date_is_required()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'started_at' => ''
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'started_at' => today()->toDateString()
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function a_stable_started_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'started_at' => 'not-a-datetime'
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('started_at');
    }

    /** @test */
    public function wrestlers_are_required_if_there_is_only_one_tag_team_included()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => [$tagteam->getKey()],
            'wrestlers' => null,
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function wrestlers_must_be_an_array()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => 'not-an-array',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function each_wrestler_must_be_an_integer()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => ['not-an-integer'],
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function each_wrestler_must_exist()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => [99],
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_hired_in_the_future_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('future')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => [$wrestler->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_must_be_active_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => [$wrestler->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs('administrator');
        $otherStable = factory(Stable::class)->states('active')->create();
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'wrestlers' => [$otherStable->wrestlers->first()->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function tag_teams_must_be_an_array()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => 'not-an-array',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function tag_teams_are_required_if_there_is_only_two_wrestlers_included()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $wrestlers = factory(Wrestler::class, 2)->states('active')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => null,
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams');
    }

    /** @test */
    public function each_tag_team_must_be_an_integer()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => ['not-an-integer'],
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function each_tag_team_must_exist()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => [99],
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_hired_in_the_future_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('future')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => [$tagteam->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_must_be_active_to_be_added_to_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => [$tagteam->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams.*');
    }

    /** @test */
    public function a_tag_team_cannot_be_apart_of_multiple_active_stables_at_the_same_time()
    {
        $this->actAs('administrator');
        $otherStable = factory(Stable::class)->states('active')->create();
        $stable = factory(Stable::class)->states('active')->create();

        $response = $this->patch(route('stables.update', $stable), $this->validParams([
            'tagteams' => [$otherStable->tagteams->first()->getKey()]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('tagteams.*');
    }
}
