<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\EmployRequest;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_manager_and_redirects($administrators)
    {
        $manager = Manager::factory()->unemployed()->create();

        $this->assertCount(0, $manager->employments);
        $this->assertEquals(ManagerStatus::UNEMPLOYED, $manager->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(1, $manager->employments);
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_manager_and_redirects($administrators)
    {
        $manager = Manager::factory()->withFutureEmployment()->create();
        $startedAt = $manager->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(ManagerStatus::FUTURE_EMPLOYMENT, $manager->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) use ($startedAt) {
            $this->assertTrue($manager->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_manager_and_redirects($administrators)
    {
        $manager = Manager::factory()->released()->create();

        $this->assertEquals(ManagerStatus::RELEASED, $manager->status);

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager))
            ->assertRedirect(action([ManagersController::class, 'index']));

        tap($manager->fresh(), function ($manager) {
            $this->assertCount(2, $manager->employments);
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_manager()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::BASIC)
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
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_employing_an_available_manager($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->available()->create();

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_employing_a_retired_manager($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_employing_a_suspended_manager($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->suspended()->create();

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_throws_exception_for_employing_an_injured_manager($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this
            ->actAs($administrators)
            ->patch(action([EmployController::class], $manager));
    }
}
