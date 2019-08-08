<?php

namespace Tests\Feature\Guest\Stables;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group guests
 */
class UpdateStableFailureConditionsTest extends TestCase
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
        $wrestlers = factory(Wrestler::class, 1)->states('bookable')->create();
        $tagteams = factory(TagTeam::class, 1)->states('bookable')->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
            'tagteams' => $overrides['tagteams'] ?? $tagteams->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('roster.stables.edit', $stable));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function a_guest_cannot_update_a_stable()
    {
        $stable = factory(Stable::class)->create();

        $response = $this->put(route('roster.stables.update', $stable), $this->validParams());

        $response->assertRedirect(route('login'));
    }
}
