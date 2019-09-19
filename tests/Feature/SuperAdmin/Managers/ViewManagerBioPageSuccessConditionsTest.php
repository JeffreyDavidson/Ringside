<?php

namespace Tests\Feature\SuperAdmin\Managers;

use App\Models\Manager;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group superadmins
 * @group roster
 */
class ViewManagerBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_a_bookable_manager_profile()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('bookable')->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }
    
    /** @test */
    public function a_super_administrator_can_view_an_injured_manager_profile()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('injured')->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_super_administrator_can_view_a_suspended_manager_profile()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('suspended')->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_super_administrator_can_view_a_retired_manager_profile()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('retired')->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }

    /** @test */
    public function a_super_administrator_can_view_a_pending_employment_manager_profile()
    {
        $this->actAs('super-administrator');
        $manager = factory(Manager::class)->states('pending-employment')->create();

        $response = $this->showRequest($manager);

        $response->assertViewIs('managers.show');
        $this->assertTrue($response->data('manager')->is($manager));
    }
}
