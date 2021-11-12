<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-rosters
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_suspends_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->suspensions);
            $this->assertEquals(WrestlerStatus::SUSPENDED, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function suspending_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));

        $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->fresh()->status);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([SuspendController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_suspend_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([SuspendController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonsuspendableWrestlerTypes
     */
    public function invoke_throws_exception_for_suspending_a_non_suspendable_wrestler($factoryState)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    public function nonsuspendableWrestlerTypes()
    {
        return [
            'unemployed wrestler' => ['unemployed'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'injured wrestler' => ['injured'],
            'released wrestler' => ['released'],
            'retired wrestler' => ['retired'],
            'suspended wrestler' => ['suspended'],
        ];
    }
}
