<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use Carbon\Carbon;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 */
class SuspendControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_suspends_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = ManagerFactory::new()->available()->create();

        $response = $this->suspendRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals($now->toDateTimeString(), $manager->fresh()->suspensions()->first()->started_at);
        });
    }

    /** @test */
    public function a_basic_user_cannot_suspend_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $this->suspendRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_suspend_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $this->suspendRequest($manager)->assertRedirect(route('login'));
    }
}
