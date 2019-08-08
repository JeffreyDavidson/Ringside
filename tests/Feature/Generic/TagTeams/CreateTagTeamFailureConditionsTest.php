<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class CreateTagTeamFailureConditionsTest extends TestCase
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
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_tag_team_name_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['name' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_name_must_be_a_unique_if_filled()
    {
        $this->actAs('administrator');
        factory(TagTeam::class)->create(['name' => 'Example Tag Team Name']);

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['name' => 'Example Tag Team Name']));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_signature_move_must_be_a_string_if_filled()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['signature_move' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('signature_move');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_started_at_must_be_a_string()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['started_at' => ['not-a-string']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_started_at_must_be_in_datetime_format()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['started_at' => now()->toDateString()]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('started_at');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_wrestlers_is_required()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => '']));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_wrestlers_must_be_an_array()
    {
        $this->actAs('administrator');

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => 'not-an-array']));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_tag_team_must_contain_two_wrestlers()
    {
        $this->actAs('administrator');
        $wrestlers = factory(Wrestler::class, 3)->states('bookable')->create();

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => $wrestlers->modelKeys()]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_be_an_integer()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id, 'not-an-integer']]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function each_value_in_the_wrestlers_array_must_exist_in_the_wrestlers_table()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id, 99]]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_wrestler_must_be_bookable_to_join_a_tag_team()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduction')->create();

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams(['wrestlers' => [$wrestler->id]]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(0, TagTeam::count());
    }

    /** @test */
    public function a_wrestler_cannot_be_a_part_of_two_bookable_tag_teams()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->from(route('tagteams.create'))
                        ->post(route('tagteams.store'), $this->validParams([
                            'wrestlers' => [$tagteam->wrestlers->first()->id]
                        ]));

        $response->assertStatus(302);
        $response->assertRedirect(route('tagteams.create'));
        $response->assertSessionHasErrors('wrestlers.*');
        $this->assertEquals(1, TagTeam::count());
    }
}
