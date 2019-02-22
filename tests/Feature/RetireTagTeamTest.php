<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetireTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.retire', $tagteam));

        $response->assertRedirect(route('tagteams.index', ['state' => 'retired']));
        $this->assertEquals(today()->toDateTimeString(), $tagteam->fresh()->retirement->started_at);
    }

    /** @test */
    public function both_wrestlers_are_retired_when_the_tag_team_retires()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.retire', $tagteam));

        $this->assertCount(1, $tagteam->wrestlers[0]->retirements);
        $this->assertCount(1, $tagteam->wrestlers[1]->retirements);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.retire', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_tag_team()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.retire', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_tag_team_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->post(route('tagteams.retire', $tagteam));

        $response->assertStatus(403);
    }
}
