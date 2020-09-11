<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Models\TagTeam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**ss
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_unretires_a_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeam::factory()->retired()->create();

        $response = $this->unretireRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        $this->assertEquals($now->toDateTimeString(), $tagTeam->fresh()->retirements()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeam::factory()->create();

        $this->unretireRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->unretireRequest($tagTeam)->assertRedirect(route('login'));
    }
}
