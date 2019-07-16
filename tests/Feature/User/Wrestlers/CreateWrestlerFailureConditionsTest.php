<?php

namespace Tests\Feature\User\Wrestlers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
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
            'started_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_wrestler()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('wrestlers.create'));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_wrestler()
    {
        $this->actAs('basic-user');

        $response = $this->from(route('wrestlers.create'))
                        ->post(route('wrestlers.store'), $this->validParams());

        $response->assertForbidden();
    }
}
