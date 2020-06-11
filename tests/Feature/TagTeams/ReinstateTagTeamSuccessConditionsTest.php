<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class ReinstateTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_reinstate_a_suspended_tag_team()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals(now()->toDateTimeString(), $tagTeam->fresh()->suspensions()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_bookable_tag_team_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_tag_team_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_retired_tag_team_cannot_be_reinstated()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeReinstatedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertForbidden();
    }
}
