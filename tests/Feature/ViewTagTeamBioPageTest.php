<?php

namespace Tests\Feature;

use App\User;
use App\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewTagTeamBioPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_tag_team_profile()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertViewIs('tagteams.show');
        $this->assertTrue($response->data('tagteam')->is($tagteam));
    }

    /** @test */
    public function a_basic_user_can_view_their_tag_team_profile()
    {
        $signedInUser = $this->actAs('basic-user');

        $tagteam = factory(TagTeam::class)->create(['user_id' => $signedInUser->id]);

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertOk();
    }

    /** @test */
    public function a_tag_teams_data_can_be_seen_on_their_profile()
    {
        $signedInUser = $this->actAs('administrator');

        $tagteam = factory(TagTeam::class)->create([
            'name' => 'Tag Team 1',
            'signature_move' => 'The Finisher',
        ]);

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertSee('Tag Team 1');
        $response->assertSee($tagteam->wrestlers[0]->weight + $tagteam->wrestlers[1]->weight);
        $response->assertSee('The Finisher');
    }

    /** @test */
    public function a_guest_cannot_view_a_tag_team_profile()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_basic_user_cannot_view_another_users_tag_team_profile()
    {
        $this->actAs('basic-user');
        $otherUser = factory(User::class)->create();
        $tagteam = factory(TagTeam::class)->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('tagteams.show', ['tagteam' => $tagteam]));

        $response->assertStatus(403);
    }
}
