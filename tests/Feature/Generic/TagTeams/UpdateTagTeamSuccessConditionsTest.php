<?php

namespace Tests\Feature\Generic\TagTeams;

use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
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
    public function wrestlers_of_tag_team_are_synced_when_tag_team_is_updated()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();
        $wrestlers = factory(Wrestler::class, 2)->states('bookable')->create();

        $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'wrestlers' => $wrestlers->modelKeys(),
        ]));

        tap($tagteam->fresh()->wrestlers, function ($tagteamWrestlers) use ($wrestlers) {
            $this->assertCount(2, $tagteamWrestlers);
            $this->assertEquals($tagteamWrestlers->modelKeys(), $wrestlers->modelKeys());
        });
    }

    /** @test */
    public function a_tag_team_name_is_optional()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'name' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('name');
    }

    /** @test */
    public function a_tag_team_signature_move_is_optional()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'signature_move' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('signature_move');
    }

    /** @test */
    public function a_tag_team_started_at_is_optional()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->create();

        $response = $this->patch(route('tagteams.update', $tagteam), $this->validParams([
            'started_at' => ''
        ]));

        $response->assertSessionDoesntHaveErrors('started_at');
    }
}
