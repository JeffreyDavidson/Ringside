<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group srm
 * @group feature-srm
 * @group roster
 * @group feature-roster
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_suspends_an_available_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::SUSPENDED, $manager->status);
            $this->assertCount(1, $manager->suspensions);
            $this->assertEquals($now->toDateTimeString(), $manager->suspensions->first()->started_at->toDateTimeString());
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

        $this->actAs(Role::BASIC)
            ->patch(route('managers.suspend', $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->patch(route('managers.suspend', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_an_injured_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function suspending_a_suspended_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeSuspendedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('managers.suspend', $manager));
    }
}
