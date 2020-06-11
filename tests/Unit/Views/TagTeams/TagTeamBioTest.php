<?php

namespace Tests\Unit\Views\TagTeams;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

class TagTeamBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_tag_team_name_can_be_seen_on_their_biography_page()
    {
        $tagTeam = TagTeamFactory::new()->create(['name' => 'The Greatest Tag Team']);

        $this->assertView('tagteams.show', compact('tagTeam'))->contains('The Greatest Tag Team');
    }

    /** @test */
    public function an_employed_tag_teams_signature_move_can_be_seen_on_their_profile()
    {
        $tagTeam = TagTeamFactory::new()
            ->employed()
            ->create([
                'signature_move' => 'The Finisher',
            ]);

        $response = $this->showRequest($tagTeam);

        $response->assertSee('The Finisher');
    }

    /** @test */
    public function an_employed_tag_teams_combined_weight_can_be_seen_on_their_profile()
    {
        $tagTeam = TagTeamFactory::new()
            ->withExistingWrestlers([
                WrestlerFactory::new()->bookable()->create(['weight' => 200]),
                WrestlerFactory::new()->bookable()->create(['weight' => 320]),
            ])->employed()
            ->create([]);

        $response = $this->showRequest($tagTeam);

        $response->assertSee('520 lbs.');
    }
}
