<?php

namespace Tests\Feature;

use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->delete(route('tagteams.unretire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));

        $this->assertNotNull($tagteam->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_tag_team()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->delete(route('tagteams.unretire', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_tag_team()
    {
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->delete(route('tagteams.unretire', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_tag_team_cannot_unretire()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.unretire', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function unretiring_a_tag_team_makes_both_wrestlers_active()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();
        $tagteam->wrestlers->first()->unretire();

        $response = $this->delete(route('tagteams.unretire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertCount(2, $tagteam->wrestlers->filter->isActive());
    }
}

