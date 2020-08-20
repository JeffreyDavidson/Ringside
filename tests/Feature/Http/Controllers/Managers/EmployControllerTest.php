<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use App\Enums\ManagerStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
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
    public function invoke_employs_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = ManagerFactory::new()->withFutureEmployment()->create();

        $response = $this->employRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
            $this->assertCount(1, $manager->employments);
            $this->assertEquals($now->toDateTimeString(), $manager->employments->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_employ_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_employ_a_manager()
    {
        $manager = ManagerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertRedirect(route('login'));
    }
}
