<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\InjureRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_injures_a_bookable_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->assertCount(0, $wrestler->injuries);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->injuries);
            $this->assertEquals(WrestlerStatus::INJURED, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function injuring_a_bookable_wrestler_on_a_bookable_tag_team_makes_tag_team_unbookable()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $wrestler = $tagTeam->currentWrestlers()->first();

        $this->assertEquals(TagTeamStatus::BOOKABLE, $tagTeam->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));

        tap($tagTeam->fresh(), function ($tagTeam) {
            $this->assertEquals(TagTeamStatus::UNBOOKABLE, $tagTeam->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(InjureController::class, '__invoke', InjureRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_injure_a_wrestler()
    {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([InjureController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_injure_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([InjureController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_an_unemployed_wrestler()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_suspended_wrestler()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_released_wrestler()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_a_future_employed_wrestler()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_injuring_a_retired_wrestler_throws()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_injuring_an_injured_wrestler()
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([InjureController::class], $wrestler));
    }
}
