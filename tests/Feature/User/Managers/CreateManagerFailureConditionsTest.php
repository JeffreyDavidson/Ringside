<?php

namespace Tests\Feature\User\Manager;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group managers
 * @group users
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
        $this->actAs('basic-user');

        $response = $this->get(route('managers.create'));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_manager()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('managers.store'), $this->validParams());

        $response->assertForbidden();
    }
}
