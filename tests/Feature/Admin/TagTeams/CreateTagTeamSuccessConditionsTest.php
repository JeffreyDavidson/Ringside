<?php

namespace Tests\Feature\Admin\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group admins
 */
class CreateTagTeamSuccessConditionsTest extends TestCase
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
    public function an_administrator_can_view_the_form_for_creating_a_tag_team()
    {
        $this->actAs('administrator');

        $response = $this->get(route('tagteams.create'));

        $response->assertViewIs('tagteams.create');
        $response->assertViewHas('tagteam', new TagTeam);
    }

    /** @test */
    public function an_administrator_can_create_a_tag_team()
    {
        $this->actAs('administrator');

        $response = $this->post(route('tagteams.store'), $this->validParams());

        $response->assertRedirect(route('tagteams.index'));
        tap(TagTeam::first(), function ($tagteam) {
            $this->assertEquals('Example Tag Team Name', $tagteam->name);
            $this->assertEquals('The Finisher', $tagteam->signature_move);
        });
    }
}
