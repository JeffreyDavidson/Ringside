<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Requests\Managers\EmployRequest;
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
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_future_employed_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->withFutureEmployment()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_an_unemployed_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->unemployed()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_employs_a_released_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->released()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(2, $manager->employments);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->last()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            EmployController::class,
            '__invoke',
            EmployRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_manager()
    {
        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_an_available_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->available()->create();

        $this->employRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->retired()->create();

        $this->employRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_a_suspended_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->suspended()->create();

        $this->employRequest($manager);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function employing_an_injured_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->injured()->create();

        $this->employRequest($manager);
    }
}
