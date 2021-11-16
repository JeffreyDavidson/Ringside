<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_employs_an_unemployed_manager_and_redirects()
    {
        $manager = Manager::factory()->unemployed()->create();

        $this->assertCount(0, $manager->employments);
        $this->assertEquals(ManagerStatus::unemployed(), $manager->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals(ManagerStatus::available(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_future_employed_manager_and_redirects()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();
        $startedAt = $manager->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(ManagerStatus::future_employment(), $manager->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($startedAt) {
            $this->assertTrue($manager->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(ManagerStatus::available(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_released_manager_and_redirects()
    {
        $manager = Manager::factory()->released()->create();

        $this->assertEquals(ManagerStatus::released(), $manager->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(2, $manager->employments);
            $this->assertEquals(ManagerStatus::available(), $manager->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_manager()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([EmployController::class], $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_manager()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonemployableManagerTypes
     */
    public function invoke_throws_exception_for_employing_a_non_employable_manager($factoryState)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $manager));
    }

    public function nonemployableManagerTypes()
    {
        return [
            'suspended manager' => ['suspended'],
            'injured manager' => ['injured'],
            'available manager' => ['available'],
            'retired manager' => ['retired'],
        ];
    }
}
