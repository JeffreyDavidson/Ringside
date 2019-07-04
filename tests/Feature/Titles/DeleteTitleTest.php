<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** @group titles */
class DeleteTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->delete(route('titles.destroy', $title));

        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_title()
    {
        $title = factory(Title::class)->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect('/login');
    }
}
