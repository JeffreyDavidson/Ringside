<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\ManagerStatus;
use App\Enums\Role;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Managers\ReleaseController;
use App\Http\Requests\Managers\ReleaseRequest;
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
 * @group feature-rosters
 */
class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_an_available_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_an_injured_manager_and_redirects($administrators)
    {
        $this->withoutExceptionHandling();
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->injured()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->ended_at->toDateTimeString());
            $this->assertEquals($now->toDateTimeString(), $manager->injuries->first()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_releases_a_suspended_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->suspended()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager))
            ->assertRedirect(route('managers.index'));

        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::RELEASED, $manager->status);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->ended_at->toDateTimeString());
            $this->assertEquals($now->toDateTimeString(), $manager->suspensions->first()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ReleaseController::class, '__invoke', ReleaseRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('managers.release', $manager))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_release_a_manager()
    {
        $manager = Manager::factory()->create();

        $this->patch(route('managers.release', $manager))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_an_unemployed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->unemployed()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_future_employed_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->withFutureEmployment()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_released_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->released()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function releasing_a_retired_manager_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeReleasedException::class);
        $this->withoutExceptionHandling();

        $manager = Manager::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('managers.release', $manager));
    }
}
