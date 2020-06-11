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
class DeleteWrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_wrestler()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->deleteRequest($wrestler);

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted($wrestler);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_wrestler()
    {
        $this->actAs(Role::BASIC);
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->deleteRequest($wrestler);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_wrestler()
    {
        $wrestler = WrestlerFactory::new()->create();

        $response = $this->deleteRequest($wrestler);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_soft_deleted_wrestler_cannot_be_deleted()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $wrestler = WrestlerFactory::new()->softDeleted()->create();

        $response = $this->deleteRequest($wrestler);

        $response->assertNotFound();
    }
}
