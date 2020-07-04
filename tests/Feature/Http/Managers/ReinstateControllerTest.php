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
class ReinstateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_reinstates_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = ManagerFactory::new()->suspended()->create();

        $response = $this->reinstateRequest($manager);

        $response->assertRedirect(route('managers.index'));
        tap($manager->fresh(), function ($manager) use ($now) {
            $this->assertEquals($now->toDateTimeString(), $manager->suspensions()->latest()->first()->ended_at);
        });
    }

    /** @test */
    public function a_basic_user_cannot_reinstate_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->create();

        $this->reinstateRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_reinstate_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $this->reinstateRequest($manager)->assertRedirect(route('login'));
    }
}
