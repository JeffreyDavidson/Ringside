<?php

namespace Tests\Feature\TagTeams;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

/**
 * @group tagteams
 * @group roster
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
        $wrestlers = WrestlerFactory::new()->count(2)->bookable()->create();

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
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->editRequest($tagTeam);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_tagteam()
    {
        $this->actAs(Role::BASIC);
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_tagteam()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->editRequest($tagTeam);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_tagteam()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $response = $this->updateRequest($tagTeam, $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
