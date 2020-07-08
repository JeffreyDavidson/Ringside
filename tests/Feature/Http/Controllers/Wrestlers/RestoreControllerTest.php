<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_restores_a_deleted_wrestler_and_redirects()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNull($wrestler->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $this->restoreRequest($wrestler)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $this->restoreRequest($wrestler)->assertRedirect(route('login'));
    }
}
