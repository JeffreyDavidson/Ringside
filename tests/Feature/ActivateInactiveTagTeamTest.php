<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivateInactiveTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->post(route('tagteams.activate', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        tap($tagteam->fresh(), function ($tagteam) {
            $this->assertTrue($tagteam->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->post(route('tagteams.activate', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->post(route('tagteams.activate', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_active_tag_team_cannot_be_activated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->post(route('tagteams.activate', $tagteam));

        $response->assertStatus(403);
    }
}
