<?php

namespace Tests\Feature\Guest\Referees;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group guests
 * @group roster
 */
class CreateRefereeFailureConditionsTest extends TestCase
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
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $response = $this->createRequest('referee');

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_referee()
    {
        $response = $this->storeRequest('referee', $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
