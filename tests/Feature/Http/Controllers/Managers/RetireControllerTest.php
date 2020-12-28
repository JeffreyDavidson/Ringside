<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Managers\RetireController;
use App\Http\Requests\Managers\RetireRequest;
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
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_available_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->available()->create();

        $response = $this->patch(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals($now->toDateTimeString(), $manager->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_injured_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->injured()->create();

        $response = $this->patch(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals($now->toDateTimeString(), $manager->retirements->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_a_suspended_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = Manager::factory()->suspended()->create();

        $response = $this->patch(route('managers.retire', $manager));

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RETIRED, $manager->status);
            $this->assertCount(1, $manager->retirements);
            $this->assertEquals($now->toDateTimeString(), $manager->retirements->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(RetireController::class, '__invoke', RetireRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = Manager::factory()->create();

        $this->patch(route('managers.retire', $manager))->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->patch(route('managers.retire', $manager))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->retired()->create();

        $this->patch(route('managers.retire', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->patch(route('managers.retire', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->released()->create();

        $this->patch(route('managers.retire', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $manager = Manager::factory()->retired()->create();

        $this->patch(route('managers.retire', $manager));
    }
}
