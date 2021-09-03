<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Http\Requests\Managers\ReinstateRequest;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_manager_and_redirects()
    {
        $manager = Manager::factory()->suspended()->create();

        $this->assertNull($manager->currentSuspension->ended_at);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->suspensions->last()->ended_at);
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReinstateController::class, '__invoke', ReinstateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_reinstate_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ReinstateController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_reinstate_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->patch(action([ReinstateController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_available_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->available()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_unemployed_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_an_injured_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_released_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_future_employed_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_reinstating_a_retired_manager()
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ReinstateController::class], $manager));
    }
}
