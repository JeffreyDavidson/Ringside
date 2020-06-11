<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class RestoreTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_restore_a_deleted_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertNull($title->fresh()->deleted_at);
    }

    /** @test */
    public function a_basic_user_cannot_restore_a_deleted_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->softDeleted()->create();

        $response = $this->put(route('titles.restore', $title));

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_restore_a_deleted_title()
    {
        $title = TitleFactory::new()->softDeleted()->create();

        $response = $this->restoreRequest($title);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_non_deleted_title_cannot_be_restored()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->create();

        $response = $this->restoreRequest($title);

        $response->assertNotFound();
    }
}
