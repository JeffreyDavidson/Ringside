<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_restores_a_deleted_title_and_redirects($administrators)
    {
        $title = Title::factory()->softDeleted()->create();

        $this->actAs($administrators)
            ->patch(route('titles.restore', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) {
            $this->assertNull($title->deleted_at);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_title()
    {
        $title = Title::factory()->softDeleted()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('titles.restore', $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_title()
    {
        $title = Title::factory()->softDeleted()->create();

        $this->patch(route('titles.restore', $title))
            ->assertRedirect(route('login'));
    }
}
