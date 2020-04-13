<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\TitleFactory;

/**
 * @group titles
 */
class DeleteTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

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