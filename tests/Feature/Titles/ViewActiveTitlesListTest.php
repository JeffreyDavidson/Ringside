<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewActiveTitlesListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_active_titles()
    {
        $this->actAs('administrator');
        $activeTitles = factory(Title::class, 3)->states('active')->create();
        $inactiveTitle = factory(Title::class)->states('inactive')->create();

        $response = $this->get(route('titles.index'));

        $response->assertOk();
        $response->assertSee(e($activeTitles[0]->name));
        $response->assertSee(e($activeTitles[1]->name));
        $response->assertSee(e($activeTitles[2]->name));
        $response->assertDontSee(e($inactiveTitle->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_active_titles()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('active')->create();

        $response = $this->get(route('titles.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_active_titles()
    {
        $title = factory(Title::class)->states('active')->create();

        $response = $this->get(route('titles.index'));

        $response->assertRedirect('/login');
    }
}
