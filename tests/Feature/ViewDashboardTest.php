<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewDashboardTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function an_administrator_can_access_the_dashboard_if_signed_in()
    {
        $this->actAs('administrator');

        $response = $this->get(route('dashboard'));

        $response->assertViewIs('dashboard');
    }
}
