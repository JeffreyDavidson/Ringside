<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class UnretireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_tag_team_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_suspended_tag_team_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_employment_tag_team_cannot_be_unretired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeUnretiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertForbidden();
    }
}
