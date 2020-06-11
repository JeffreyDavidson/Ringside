<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class EmployTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_employ_a_pending_employment_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_an_pending_employment_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_tag_team_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_tag_team_without_wrestlers_cannot_be_employed()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeEmployedException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertForbidden();
    }
}
