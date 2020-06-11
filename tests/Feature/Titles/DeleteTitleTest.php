<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class DeleteTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_an_active_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->active()->create();

        $response = $this->deleteRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_future_activation_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->futureActivation()->create();

        $response = $this->delete(route('titles.destroy', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted('titles', ['name' => $title->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_title()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->retired()->create();

        $response = $this->deleteRequest($title);

        $response->assertRedirect(route('titles.index'));
        $this->assertSoftDeleted($title);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->create();

        $response = $this->deleteRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_delete_a_title()
    {
        $title = TitleFactory::new()->create();

        $response = $this->deleteRequest($title);

        $response->assertRedirect(route('login'));
    }
}
