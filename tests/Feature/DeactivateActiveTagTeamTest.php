<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->post(route('tagteams.deactivate', $tagteam));

        $response->assertRedirect(route('tagteams.index', ['state' => 'inactive']));
        tap($tagteam->fresh(), function ($tagteam) {
            $this->assertFalse($tagteam->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->post(route('tagteams.deactivate', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('active')->create();

        $response = $this->post(route('tagteams.deactivate', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_tag_team_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('inactive')->create();

        $response = $this->post(route('tagteams.deactivate', $tagteam));

        $response->assertStatus(403);
    }
}
