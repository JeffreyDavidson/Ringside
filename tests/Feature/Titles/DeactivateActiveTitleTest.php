<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeactivateActiveTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_deactivate_an_active_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('active')->create();

        $response = $this->post(route('titles.deactivate', $title));

        $response->assertRedirect(route('titles.index', ['state' => 'inactive']));
        tap($title->fresh(), function ($title) {
            $this->assertFalse($title->is_active);
        });
    }

    /** @test */
    public function a_basic_user_cannot_deactivate_an_active_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('active')->create();

        $response = $this->post(route('titles.deactivate', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_deactivate_an_active_title()
    {
        $title = factory(Title::class)->states('active')->create();

        $response = $this->post(route('titles.deactivate', $title));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_inactive_title_cannot_be_deactivated()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('inactive')->create();

        $response = $this->post(route('titles.deactivate', $title));

        $response->assertStatus(403);
    }
}
