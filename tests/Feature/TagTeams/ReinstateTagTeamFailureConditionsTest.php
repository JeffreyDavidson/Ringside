<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class ReinstateTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_reinstate_a_suspended_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_suspended_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }
}
