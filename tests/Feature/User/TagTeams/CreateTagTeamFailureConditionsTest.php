<?php

namespace Tests\Feature\User\TagTeams;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class CreateTagTeamFailureConditionsTest extends TestCase
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
        $wrestlers = factory(Wrestler::class, 2)->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('tagteams.create'));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_tag_team()
    {
        $this->actAs('basic-user');

        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertForbidden();
    }
}
