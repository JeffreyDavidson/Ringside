<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Requests\Managers\InjureRequest;
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
class InjureControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_injures_an_available_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->available()->create();

        $response = $this->injureRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::INJURED, $manager->status);
            $this->assertCount(1, $manager->injuries);
            $this->assertEquals($now->toDateTimeString(), $manager->injuries->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            InjureController::class,
            '__invoke',
            InjureRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->injureRequest($manager)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->unemployed()->create();

        $this->injureRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_suspended_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->suspended()->create();

        $this->injureRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->released()->create();

        $this->injureRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->injureRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->retired()->create();

        $this->injureRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function injuring_an_injured_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeInjuredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->injured()->create();

        $this->injureRequest($manager);
    }
}