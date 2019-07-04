<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group titles */
class ActivateTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_activate_an_inactive_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('inactive')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) {
            $this->assertTrue($title->is_usable);
            $this->assertEquals(now()->toDateTimeString(), $title->introduced_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_an_inactive_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('inactive')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_activate_an_inactive_title()
    {
        $title = factory(Title::class)->states('inactive')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_active_title_cannot_be_activated()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('active')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertStatus(403);
    }
}
