<?php

namespace Tests\Integration\Rules;

use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Rules\CannotBelongToMultipleEmployedTagTeams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class CannotBelongToMultipleEmployedTagTeamsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function a_wrestler_cannot_belong_to_multiple_bookable_tag_teams()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();
        $tagTeam2 = TagTeam::factory()->unemployed()->create();

        $this->assertFalse((new CannotBelongToMultipleEmployedTagTeams($tagTeam2))->passes(null, $tagTeam->currentWrestlers->first()->id));
    }

    /**
     * @test
     */
    public function a_wrestler_not_on_a_bookable_tag_team_can_be_added_to_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->unemployed()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $this->assertTrue((new CannotBelongToMultipleEmployedTagTeams($tagTeam))->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_wrestler_on_a_bookable_tag_team_can_be_added_to_a_tag_team()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->assertTrue((new CannotBelongToMultipleEmployedTagTeams($tagTeam))->passes(null, $tagTeam->currentWrestlers->first()->id));
    }
}
