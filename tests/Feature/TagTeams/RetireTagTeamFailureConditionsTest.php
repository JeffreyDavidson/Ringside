<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class RetireTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_bookable_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $response = $this->retireRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->retireRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_retired_tag_team_cannot_be_retired()
    {
        $this->withoutExceptionHandling();
        $this->expectException(CannotBeRetiredException::class);

        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $response = $this->retireRequest($tagTeam);

        $response->assertForbidden();
    }
}
