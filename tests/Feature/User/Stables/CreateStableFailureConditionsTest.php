<?php

namespace Tests\Feature\User\Stables;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group users
 * @group roster
 */
class CreateStableFailureConditionsTest extends TestCase
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
        $wrestler = WrestlerFactory::new()->bookable()->create();
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestler->getKey()],
            'tagteams' => [$tagTeam->getKey()],
        ], $overrides);
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_stable()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('stables.create');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_stable()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('stables', $this->validParams());

        $response->assertForbidden();
    }
}
