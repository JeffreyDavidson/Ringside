<?php

namespace Tests\Feature\Admin\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class UpdateTagTeamSuccessConditionsTest extends TestCase
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

        return array_replace([
            'name' => 'Example Tag Team Name',
            'signature_move' => 'The Finisher',
            'started_at' => now()->toDateTimeString(),
            'wrestlers' => $overrides['wrestlers'] ?? $wrestlers->modelKeys(),
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_tagteam()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->get(route('tagteams.edit', $tagteam));

        $response->assertViewIs('tagteams.edit');
        $this->assertTrue($response->data('tagteam')->is($tagteam));
    }

    /** @test */
    public function an_administrator_can_update_a_tag_team()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams());

        $response->assertRedirect(route('tagteams.index'));
        tap($tagteam->fresh(), function ($tagteam) {
            $this->assertEquals('Example Tag Team Name', $tagteam->name);
            $this->assertEquals('The Finisher', $tagteam->signature_move);
        });
    }
}
