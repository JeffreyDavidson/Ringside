<?php

namespace Tests\Feature\User\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group users
 */
class UpdateTagTeamFailureConditionsTest extends TestCase
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

        return array_replace([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'hired_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.edit', $tagteam));

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_tagteam()
    {
        $this->actAs('basic-user');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams());

        $response->assertForbidden();
    }
}
