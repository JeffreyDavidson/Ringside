<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SuspendTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_suspend_a_tagteam()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.suspend', $tagteam));

        $response->assertRedirect(route('tagteams.index', ['state' => 'suspended']));
        $this->assertEquals(today()->toDateTimeString(), $tagteam->fresh()->suspension->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.suspend', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_suspend_a_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->post(route('tagteams.suspend', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_suspended_tagteam_cannot_be_suspended_again()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->post(route('tagteams.suspend', $tagteam));

        $response->assertStatus(403);
    }
}
