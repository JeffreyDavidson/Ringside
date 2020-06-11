<?php

namespace Tests\Feature\Wrestlers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group wrestlers
 * @group roster
 */
class RestoreWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertNull($wrestler->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_wrestler()
    {
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_bookable_wrestler_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->bookable()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertNotFound();
    }

    /** @test */
    public function a_pending_employment_wrestler_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->pendingEmployment()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertNotFound();
    }

    /** @test */
    public function a_retired_wrestler_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->retired()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertNotFound();
    }

    /** @test */
    public function a_suspended_wrestler_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertNotFound();
    }

    /** @test */
    public function an_injured_wrestler_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->injured()->create();

        $response = $this->restoreRequest($wrestler);

        $response->assertNotFound();
    }
}
