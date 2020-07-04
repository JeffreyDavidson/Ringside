<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class ViewManagersListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_managers_page()
    {
        $this->actAs(Role::BASIC);

        $response = $this->indexRequest('managers');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_get_managers()
    {
        $this->actAs(Role::BASIC);

        $response = $this->ajaxJson(route('managers.index'));

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_managers_page()
    {
        $response = $this->indexRequest('manager');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_get_managers()
    {
        $response = $this->ajaxJson(route('managers.index'));

        $response->assertUnauthorized();
    }
}
