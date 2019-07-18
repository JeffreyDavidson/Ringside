<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class RetireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function both_wrestlers_are_retired_when_the_tag_team_retires()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('bookable')->create();

        $this->put(route('tagteams.retire', $tagteam));

        $this->assertCount(1, $tagteam->wrestlers[0]->retirements);
        $this->assertCount(1, $tagteam->wrestlers[1]->retirements);
    }
}
