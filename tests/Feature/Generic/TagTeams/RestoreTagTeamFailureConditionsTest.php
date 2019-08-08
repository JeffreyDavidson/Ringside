<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class RestoreTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_tag_team_cannot_be_restored()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $response = $this->put(route('tagteams.restore', $tagteam));

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_introduction_tag_team_cannot_be_restored()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('pending-introduction')->create();

        $response = $this->put(route('tagteams.restore', $tagteam));

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_tag_team_cannot_be_restored()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();

        $response = $this->put(route('tagteams.restore', $tagteam));

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_tag_team_cannot_be_restored()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('suspended')->create();

        $response = $this->put(route('tagteams.restore', $tagteam));

        $response->assertNotFound();
    }
}
