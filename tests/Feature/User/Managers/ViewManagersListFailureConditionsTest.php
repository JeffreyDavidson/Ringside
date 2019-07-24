<?php

namespace Tests\Feature\User\Managers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
 */
class ViewManagersListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_managers_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('managers.index'));

        $response->assertForbidden();
    }
}
