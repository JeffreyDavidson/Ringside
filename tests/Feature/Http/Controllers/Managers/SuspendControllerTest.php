<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_suspends_an_available_manager_and_redirects()
    {
        $manager = Manager::factory()->available()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->suspensions);
            $this->assertEquals(ManagerStatus::SUSPENDED, $manager->status);
        });
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
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([SuspendController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->patch(action([SuspendController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_an_unemployed_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_future_employed_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_an_injured_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_released_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_retired_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_suspending_a_suspended_manager()
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->suspended()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }
}
