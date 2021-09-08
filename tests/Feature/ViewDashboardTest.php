<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function an_administrator_can_access_the_dashboard_if_signed_in()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->get(route('dashboard'));

        $response->assertViewIs('dashboard');
    }
}
