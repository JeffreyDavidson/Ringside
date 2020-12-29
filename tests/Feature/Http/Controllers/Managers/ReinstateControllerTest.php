<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Managers\ReinstateController;
use App\Http\Requests\Managers\ReinstateRequest;
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
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_suspended_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->suspensions);
            $this->assertEquals($now->toDateTimeString(), $manager->suspensions->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReinstateController::class, '__invoke', ReinstateRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('managers.reinstate', $manager))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->patch(route('managers.reinstate', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_available_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->available()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_an_injured_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function reinstating_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReinstatedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('managers.reinstate', $manager));
    }
}
