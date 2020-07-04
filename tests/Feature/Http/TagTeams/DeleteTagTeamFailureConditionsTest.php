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
class DeleteTagTeamFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_delete_a_tagteam()
    {
        $this->actAs(Role::BASIC);

        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->deleteRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_tagteam()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->deleteRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_already_deleted_tag_team_cannot_be_deleted()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $tagTeam = TagTeamFactory::new()->softDeleted()->create();

        $response = $this->deleteRequest($tagTeam);

        $response->assertNotFound();
    }
}
