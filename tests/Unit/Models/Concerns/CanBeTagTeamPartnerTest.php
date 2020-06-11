<?php

namespace Tests\Unit\Models\Concerns;

use Tests\TestCase;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group roster
 * @group traits
 */
class CanBeTagTeamPartnerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function some_single_roster_members_has_a_current_tag_team_after_joining($modelClass)
    {
        $model = factory($modelClass)->states('bookable')->create();
        $tagTeam = factory(TagTeam::class)->states('bookable')->create();

        $model->tagTeamHistory()->attach($tagTeam);

        $this->assertEquals($tagTeam->id, $model->currentTagTeam->id);
        $this->assertTrue($model->tagTeamHistory->contains($tagTeam));
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_tag_team_remains_in_some_single_roster_members_history_after_leaving($modelClass)
    {
        $model = factory($modelClass)->create();
        $tagTeam = factory(TagTeam::class)->create();

        $model->tagTeamHistory()->attach($tagTeam);
        $model->tagTeamHistory()->detach($tagTeam);

        $this->assertTrue($model->previousTagTeams->contains($tagTeam));
    }

    public function modelClassDataProvider()
    {
        return [[Wrestler::class]];
    }
}
