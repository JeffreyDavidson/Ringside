<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnretireRetiredTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_unretire_a_retired_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->delete(route('titles.unretire', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertNotNull($title->fresh()->previousRetirement->ended_at);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->delete(route('titles.unretire', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_unretire_a_retired_title()
    {
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->delete(route('titles.unretire', $title));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_retired_title_cannot_unretire()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->delete(route('titles.unretire', $title));

        $response->assertStatus(403);
    }
}
