<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Models\Manager;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_reinstates_a_suspended_manager_and_redirects()
    {
        $manager = Manager::factory()->suspended()->create();

        $this->assertNull($manager->currentSuspension->ended_at);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReinstateController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->suspensions->last()->ended_at);
            $this->assertEquals(ManagerStatus::available(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_reinstate_a_manager()
    {
        $manager = Manager::factory()->create();

        $this
            ->actAs(Role::basic())
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
     *
     * @dataProvider nonreinstatableManagerTypes
     */
    public function invoke_throws_exception_for_reinstating_a_non_reinstatable_manager($factoryState)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ReinstateController::class], $manager));
    }

    public function nonreinstatableManagerTypes()
    {
        return [
            'available manager' => ['available'],
            'unemployed manager' => ['unemployed'],
            'injured manager' => ['injured'],
            'released manager' => ['released'],
            'with future employed manager' => ['withFutureEmployment'],
            'retired manager' => ['retired'],
        ];
    }
}
