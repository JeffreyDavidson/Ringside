<?php

namespace Tests\Feature\Managers;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 */
class CreateManagerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_manager()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('manager');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('manager', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_manager()
    {
        $response = $this->createRequest('manager');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_manager()
    {
        $response = $this->storeRequest('manager', $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
