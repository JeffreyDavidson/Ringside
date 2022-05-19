<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;

class ViewDashboardTest extends TestCase
{
    /**
     * @test
     */
    public function administrators_can_view_the_dashboard()
    {
        $this->actAs(Role::administrator())
            ->get(route('dashboard'))
            ->assertViewIs('dashboard');
    }

    /**
     * @test
     */
    public function basic_users_can_view_the_dashboard()
    {
        $this->actAs(Role::basic())
            ->get(route('dashboard'))
            ->assertViewIs('dashboard');
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_dashboard_page()
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
