<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\SuspendRequest;
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
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(SuspendController::class, '__invoke', SuspendRequest::class);
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
     */
    public function invoke_throws_exception_for_suspending_an_unemployed_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_future_employed_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_an_injured_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_released_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_retired_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_suspended_wrestler()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $wrestler));
    }
}
