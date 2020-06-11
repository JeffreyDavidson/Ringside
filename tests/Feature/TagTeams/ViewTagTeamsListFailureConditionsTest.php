<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 */
class ViewTagTeamsListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_tag_teams_page()
    {
        $this->actAs(Role::BASIC);

        $response = $this->indexRequest('tag-teams');

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_tag_teams_page()
    {
        $response = $this->indexRequest('tag-teams');

        $response->assertRedirect(route('login'));
    }
}
