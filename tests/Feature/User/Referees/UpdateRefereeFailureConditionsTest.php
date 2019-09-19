<?php

namespace Tests\Feature\User\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 * @group roster
 */
class UpdateRefereeFailureConditionsTest extends TestCase
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
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.edit', $referee));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->patch(route('referees.update', $referee), $this->validParams());

        $response->assertForbidden();
    }
}
