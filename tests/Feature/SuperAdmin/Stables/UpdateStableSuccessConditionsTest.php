<?php

namespace Tests\Feature\SuperAdmin\Stables;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\StableFactory;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group stables
 * @group superadmins
 * @group roster
 */
class UpdateStableSuccessConditionsTest extends TestCase
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
        $wrestlers = WrestlerFactory::new()->count(1)->bookable()->create();
        $tagTeams = TagTeamFactory::new()->count(1)->bookable()->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestlers->getKey()],
            'tagteams' => [$tagTeams->getKey()],
        ], $overrides);
    }

    /** @test */
    public function a_super_administrator_can_view_the_form_for_editing_a_stable()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);
        $stable = StableFactory::new()->create();

        $response = $this->editRequest($stable);

        $response->assertViewIs('stables.edit');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function a_super_administrator_can_update_a_stable()
    {
        $this->actAs(Role::SUPER_ADMINISTRATOR);
        $stable = StableFactory::new()->create();

        $response = $this->updateRequest($stable, $this->validParams());

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }
}
