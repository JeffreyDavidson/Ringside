<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewRetiredTitlesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_all_retired_titles()
    {
        $this->actAs('administrator');
        $retiredTitles = factory(Title::class, 3)->states('retired')->create();
        $activeTitle = factory(Title::class)->states('active')->create();

        $response = $this->get(route('titles.index', ['state' => 'retired']));

        $response->assertOk();
        $response->assertSee(e($retiredTitles[0]->name));
        $response->assertSee(e($retiredTitles[1]->name));
        $response->assertSee(e($retiredTitles[2]->name));
        $response->assertDontSee(e($activeTitle->name));
    }

    /** @test */
    public function a_basic_user_cannot_view_all_retired_titles()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->get(route('titles.index', ['state' => 'retired']));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_all_retired_titles()
    {
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->get(route('titles.index', ['state' => 'retired']));

        $response->assertRedirect('/login');
    }
}
