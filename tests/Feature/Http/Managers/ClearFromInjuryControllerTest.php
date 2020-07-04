<?php

namespace Tests\Feature\Http\Controllers\Managers;

use App\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ManagerFactory;
use Tests\TestCase;

/**
 * @group managers
 * @group feature-managers
 * @group roster
 */
class ClearFromInjuryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_marks_a_manager_as_being_recovered_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $manager = ManagerFactory::new()->injured()->create();

        $response = $this->clearInjuryRequest($manager);

        $response->assertRedirect(route('managers.index'));
        $this->assertEquals($now->toDateTimeString(), $manager->fresh()->injuries()->latest()->first()->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_mark_an_injured_manager_as_recovered()
    {
        $this->actAs(Role::BASIC);
        $manager = ManagerFactory::new()->injured()->create();

        $this->clearInjuryRequest($manager)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_mark_an_injured_manager_as_recovered()
    {
        $manager = ManagerFactory::new()->injured()->create();

        $this->clearInjuryRequest($manager)->assertRedirect(route('login'));
    }
}
