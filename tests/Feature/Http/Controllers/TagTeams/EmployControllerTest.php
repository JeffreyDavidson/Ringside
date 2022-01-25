<?php

namespace Tests\Feature\Http\Controllers\TagTeams;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use Tests\TestCase;

/**
 * @group tagteams
 * @group feature-tagteams
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_employs_an_unemployed_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->unemployed()->create();

        $this->assertCount(0, $tagTeam->employments);
        $this->assertEquals(TagTeamStatus::unemployed(), $tagTeam->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(1, $tagTeam->employments);
            $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertCount(1, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_future_employed_tag_team_and_their_tag_team_partners_and_redirects()
    {
        $tagTeam = TagTeam::factory()->withFutureEmployment()->create();
        $startedAt = $tagTeam->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(TagTeamStatus::future_employment(), $tagTeam->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) use ($startedAt) {
            $this->assertTrue($tagTeam->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertTrue($wrestler->currentEmployment->started_at->lt($startedAt));
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_released_tag_team_and_their_tag_team_partners_redirects()
    {
        $tagTeam = TagTeam::factory()->released()->create();

        $this->assertEquals(TagTeamStatus::released(), $tagTeam->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(action([TagTeamsController::class, 'index']));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertCount(2, $tagTeam->employments);
            $this->assertEquals(TagTeamStatus::bookable(), $tagTeam->status);

            foreach ($tagTeam->currentWrestlers as $wrestler) {
                $this->assertCount(2, $wrestler->employments);
                $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
            }
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([EmployController::class], $tagTeam))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->create();

        $this->patch(action([EmployController::class], $tagTeam))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * @dataProvider nonemployableTagTeamTypes
     */
    public function invoke_throws_exception_for_employing_a_non_employable_tag_team($factoryState)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $tagTeam = TagTeam::factory()->{$factoryState}()->create();

        $this->actAs(Role::administrator())
            ->patch(action([EmployController::class], $tagTeam));
    }

    public function nonemployableTagTeamTypes()
    {
        return [
            'bookable tag team' => ['bookable'],
            'retired tag team' => ['retired'],
            'suspended tag team' => ['suspended'],
        ];
    }
}
