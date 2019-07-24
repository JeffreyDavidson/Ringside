<?php

namespace Tests\Feature\Guest\Managers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group guests
 */
class ViewManagersListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_guest_cannot_view_managers_page()
    {
        $response = $this->get(route('managers.index'));

        $response->assertRedirect(route('login'));
    }
}
