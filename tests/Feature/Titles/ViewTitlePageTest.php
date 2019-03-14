<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\User;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ViewTitlePageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertViewIs('titles.show');
        $this->assertTrue($response->data('title')->is($title));
    }

    /** @test */
    public function a_basic_user_can_view_a_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_titles_data_can_be_seen_on_their_profile()
    {
        $this->actAs('administrator');

        $title = factory(Title::class)->create([
            'name' => 'Title 1',
        ]);

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertSee('Title 1');
    }

    /** @test */
    public function a_guest_cannot_view_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->get(route('titles.show', ['title' => $title]));

        $response->assertRedirect('/login');
    }
}
