<?php

namespace Tests\Feature\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_tagteam()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('tagteams.restore', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertNull($tagteam->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('tagteams.restore', $tagteam));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_tagteam()
    {
        $tagteam = factory(TagTeam::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('tagteams.restore', $tagteam));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_tagteam_cannot_be_restored()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.restore', $tagteam));

        $response->assertStatus(404);
    }
}
