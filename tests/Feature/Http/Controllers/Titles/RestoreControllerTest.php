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

    /** @test */
    public function invoke_restores_a_deleted_title_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = Title::factory()->softDeleted()->create();

        $response = $this->restoreRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertNull($title->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->softDeleted()->create();

        $this->restoreRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_title()
    {
        $title = Title::factory()->softDeleted()->create();

        $this->restoreRequest($title)->assertRedirect(route('login'));
    }
}
