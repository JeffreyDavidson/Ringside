<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_tagteam()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $this->assertSoftDeleted('tag_teams', ['name' => $tagteam->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->delete(route('tagteams.destroy', $tagteam));

        $response->assertRedirect('/login');
    }
}
