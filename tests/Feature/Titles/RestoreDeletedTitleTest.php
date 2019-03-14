<?php

namespace Tests\Feature\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RestoreDeletedTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('titles.restore', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertNull($title->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('titles.restore', $title));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_title()
    {
        $title = factory(Title::class)->create(['deleted_at' => today()->subDays(3)->toDateTimeString()]);

        $response = $this->patch(route('titles.restore', $title));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_non_deleted_title_cannot_be_restored()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->patch(route('titles.restore', $title));

        $response->assertStatus(404);
    }
}
