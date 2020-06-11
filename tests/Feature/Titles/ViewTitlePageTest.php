<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class ViewTitlePageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_title_page()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()->create();

        $response = $this->showRequest($title);

        $response->assertViewIs('titles.show');
        $this->assertTrue($response->data('title')->is($title));
    }

    /** @test */
    public function a_basic_user_can_view_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->create();

        $response = $this->showRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_a_title()
    {
        $title = TitleFactory::new()->create();

        $response = $this->showRequest($title);

        $response->assertRedirect(route('login'));
    }
}
