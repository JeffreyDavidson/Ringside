<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\ManagerFactory;

/**
 * @group managers
 * @group feature-managers
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
    public function invoke_injures_a_manager_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = ManagerFactory::new()->available()->create();

        $response = $this->injureRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals($now->toDateTimeString(), $manager->fresh()->currentInjury->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_injure_a_manager()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->withFutureEmployment()->create();

        $this->employRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_injure_a_manager()
    {
        $manager = ManagerFactory::new()->create();

        $this->injureRequest($manager)->assertRedirect(route('login'));
    }
}
