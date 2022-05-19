<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class ClearInjuryControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_marks_an_injured_manager_as_being_cleared_and_redirects()
    {
        $manager = Manager::factory()->injured()->create();

        $this->assertNull($manager->injuries->last()->ended_at);
        $this->assertEquals(ManagerStatus::injured(), $manager->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([ClearInjuryController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertNotNull($manager->injuries->last()->ended_at);
            $this->assertEquals(ManagerStatus::available(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_mark_an_injured_manager_as_cleared()
    {
        $manager = Manager::factory()->injured()->create();

        $this->actAs(Role::basic())
            ->patch(action([ClearInjuryController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_mark_an_injured_manager_as_cleared()
    {
        $manager = Manager::factory()->injured()->create();

        $this->patch(action([ClearInjuryController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     *
     * @dataProvider nonclearableManagerTypes
     */
    public function invoke_throws_exception_for_clearing_an_injury_from_a_non_clearable_manager($factoryState)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([ClearInjuryController::class], $manager));
    }

    public function nonclearableManagerTypes()
    {
        return [
            'unemployed manager' => ['unemployed'],
            'available manager' => ['available'],
            'with future employed manager' => ['withFutureEmployment'],
            'suspended manager' => ['suspended'],
            'retired manager' => ['retired'],
            'released manager' => ['released'],
        ];
    }
}
