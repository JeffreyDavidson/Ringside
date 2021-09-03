<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function an_administrator_can_access_the_dashboard_if_signed_in($administrators)
    {
        $this->actAs($administrators);

        $response = $this->get(route('dashboard'));

        $response->assertViewIs('dashboard');
    }
}
