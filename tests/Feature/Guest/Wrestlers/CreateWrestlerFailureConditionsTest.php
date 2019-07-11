<?php

namespace Tests\Feature\Guest\Wrestlers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group guests
 */
class CreateWrestlerFailureConditionsTest extends TestCase
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
            'name' => 'Example Wrestler Name',
            'feet' => '6',
            'inches' => '4',
            'weight' => '240',
            'hometown' => 'Laraville, FL',
            'signature_move' => 'The Finisher',
            'hired_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_wrestler()
    {
        $response = $this->get(route('wrestlers.create'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_wrestler()
    {
        $response = $this->post(route('wrestlers.store'), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
