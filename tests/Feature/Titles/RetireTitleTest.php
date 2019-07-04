<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group titles */
class RetireTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertEquals(today()->toDateTimeString(), $title->fresh()->retirement->started_at);
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_retire_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_retired_title_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function an_inactive_title_cannot_be_retired()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('inactive')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertStatus(403);
    }
}
