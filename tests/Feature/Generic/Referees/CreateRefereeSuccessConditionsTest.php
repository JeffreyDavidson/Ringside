<?php

namespace Tests\Feature\Generic\Referees;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class CreateRefereeSuccessConditionsTest extends TestCase
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
    public function a_referee_started_at_date_is_optional()
    {
        $this->actAs('administrator');

        $response = $this->from(route('referees.create'))
                        ->post(route('referees.store'), $this->validParams([
                            'started_at' => ''
                        ]));

        $response->assertSessionDoesntHaveErrors('started_at');
    }
}
