<?php

namespace Tests\Feature;

use App\TagTeam;
use App\Wrestler;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateTagTeamTest extends TestCase
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
        $wrestlers = factory(Wrestler::class, 2)->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'hired_at' => $wrestlers->first()->hired_at->toDateTimeString(),
            'wrestlers' => $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs('administrator');

        $response = $this->get(route('tagteams.create'));

        $response->assertViewIs('tagteams.create');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('tagteams.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $response = $this->get(route('tagteams.create'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_create_a_tag_team()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertRedirect(route('tagteams.index'));
        tap(TagTeam::first(), function ($tagteam) {
            $this->assertEquals('Example Tag Team Name', $tagteam->name);
            $this->assertEquals('The Finisher', $tagteam->signature_move);
        });
    }

    /** @test */
    public function a_tag_team_slug_is_generated_when_created()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams());

        tap(TagTeam::first(), function ($tagteam) {
            $this->assertEquals('example-tag-team-name', $tagteam->slug);
        });
    }

    /** @test */
    public function a_tag_team_hired_today_or_before_is_active()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['hired_at' => today()->toDateTimeString()]));

        tap(TagTeam::first(), function ($tagteam) {
            $this->assertTrue($tagteam->is_active);
        });
    }

    /** @test */
    public function a_tag_team_hired_after_today_is_inactive()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['hired_at' => Carbon::tomorrow()->toDateTimeString()]));

        tap(TagTeam::first(), function ($tagteam) {
            $this->assertFalse($tagteam->is_active);
        });
    }

    /** @test */
    public function two_wrestlers_make_a_tag_team()
    {
        $this->actAs('administrator');

        $wrestlers = factory(Wrestler::class, 2)->states('active')->create()->modelKeys();

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => $wrestlers]));

        tap(TagTeam::first(), function ($tagteam) use ($wrestlers) {
            $this->assertCount(2, $tagteam->wrestlers);
            $this->assertEquals($tagteam->wrestlers->modelKeys(), $wrestlers);
        });
    }

    /** @test */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_create_a_tag_team()
    {
        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_tag_team_name_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_tag_team_signature_move_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['signature_move' => '']));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tag_team_hired_at_date_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['hired_at' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_tag_team_hired_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['hired_at' => today()->toDateString()]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_tag_team_hired_at_must_be_a_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['hired_at' => 'not-a-datetime']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hired_at');
    }

    /** @test */
    public function a_tag_team_wrestlers_is_required()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function a_tag_team_wrestlers_must_be_an_array()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => 'not-an-array']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function a_tag_team_must_contain_two_wrestlers()
    {
        $this->actAs('administrator');
        $wrestlers = factory(Wrestler::class, 3)->create()->modelKeys();

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => $wrestlers]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers');
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_be_an_integer()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id, 'not-an-integer']]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_exist_in_the_wrestlers_table()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('active')->create();

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id, 99]]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_who_has_not_been_hired_before_today_cannot_join_a_tag_team()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create(['hired_at' => Carbon::tomorrow()->toDateTimeString()]);

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id]]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_must_be_active_to_join_a_tag_team()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create(['hired_at' => Carbon::yesterday()->toDateTimeString()]);

        $response = $this->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id]]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }

    /** @test */
    public function a_wrestler_cannot_be_a_part_of_two_active_tag_teams()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->post(route('tagteams.store'), $this->validParams(
            ['wrestlers' => [$tagteam->wrestlers->first()->id]
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('wrestlers.*');
    }
}
