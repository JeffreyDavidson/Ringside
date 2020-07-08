<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_tag_team_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $response = $this->employRequest($tagTeam);

        $response->assertRedirect(route('tag-teams.index'));
        tap($tagTeam->fresh(), function ($tagTeam) use ($now) {
            $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals(
                $now->toDateTimeString(),
                $tagTeam->employments->first()->started_at->toDateTimeString()
            );
            $tagTeam->currentWrestlers->each(
                fn (Wrestler $wrestler) => $this->assertTrue($wrestler->isCurrentlyEmployed())
            );
        });
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_tag_team()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->employRequest($tagTeam)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->employRequest($tagTeam)->assertRedirect(route('login'));
    }
}
