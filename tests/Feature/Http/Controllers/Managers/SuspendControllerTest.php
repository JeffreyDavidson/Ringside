<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\SuspendController;
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
     * @dataProvider nonsuspendableManagerTypes
     */
    public function invoke_throws_exception_for_suspending_a_non_suspendable_manager($factoryState)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([SuspendController::class], $manager));
    }

    public function nonsuspendableManagerTypes()
    {
        return [
            'unemployed manager' => ['unemployed'],
            'with future employed manager' => ['withFutureEmployment'],
            'injured manager' => ['injured'],
            'released manager' => ['released'],
            'retired manager' => ['retired'],
            'suspended manager' => ['suspended'],
        ];
    }
}
