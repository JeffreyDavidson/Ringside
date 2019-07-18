<?php

namespace Tests\Feature\Guest\TagTeams;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group guests
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
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        return array_replace_recursive([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_creating_a_tag_team()
    {
        $response = $this->get(route('tagteams.create'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_create_a_tag_team()
    {
        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
