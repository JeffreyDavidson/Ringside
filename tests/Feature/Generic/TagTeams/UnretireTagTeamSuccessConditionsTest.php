<?php

namespace Tests\Feature\Generic\TagTeams;

use App\Models\TagTeam;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group tagteams
 * @group generics
 */
class UnretireTagTeamSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function unretiring_a_tag_team_makes_both_wrestlers_bookable()
    {
        $this->actAs('administrator');
        $tagteam = factory(TagTeam::class)->states('retired')->create();
        $tagteam->wrestlers->first()->unretire();

        $response = $this->put(route('tagteams.unretire', $tagteam));

        $response->assertRedirect(route('tagteams.index'));
        $this->assertCount(2, $tagteam->wrestlers->filter->is_bookable);
    }
}
