<?php

namespace Tests\Feature\Admin\Stables;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group admins
 */
class UpdateStableSucessConditionsTest extends TestCase
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
        $wrestlers = factory(Wrestler::class)->states('bookable')->create();
        $tagteams = factory(TagTeam::class)->states('bookable')->create();

        return array_replace([
            'name' => 'Example Stable Name',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => [$wrestlers->getKey()],
            'tagteams' => [$tagteams->getKey()],
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->get(route('roster.stables.edit', $stable));

        $response->assertViewIs('stables.edit');
        $this->assertTrue($response->data('stable')->is($stable));
    }

    /** @test */
    public function an_administrator_can_update_a_stable()
    {
        $this->actAs('administrator');
        $stable = factory(Stable::class)->create();

        $response = $this->put(route('roster.stables.update', $stable), $this->validParams());

        $response->assertRedirect(route('roster.stables.index'));
        tap($stable->fresh(), function ($stable) {
            $this->assertEquals('Example Stable Name', $stable->name);
        });
    }
}
