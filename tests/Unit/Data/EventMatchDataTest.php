<?php

use App\Data\EventMatchData;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('competitors can be separated into wrestlers and tag teams', function () {
    [$wrestlerA, $wrestlerB, $wrestlerC, $wrestlerD] = Wrestler::factory()->count(4)->create();
    [$tagTeamA, $tagTeamB, $tagTeamC, $tagTeamD] = TagTeam::factory()->count(4)->create();

    $competitors = collect([
        [
            ['competitor_type' => 'wrestler', 'competitor_id' => $wrestlerA->id],
            ['competitor_type' => 'wrestler', 'competitor_id' => $wrestlerB->id],
            ['competitor_type' => 'tag_team', 'competitor_id' => $tagTeamA->id],
            ['competitor_type' => 'tag_team', 'competitor_id' => $tagTeamB->id],
        ],
        [
            ['competitor_type' => 'wrestler', 'competitor_id' => $wrestlerC->id],
            ['competitor_type' => 'wrestler', 'competitor_id' => $wrestlerD->id],
            ['competitor_type' => 'tag_team', 'competitor_id' => $tagTeamC->id],
            ['competitor_type' => 'tag_team', 'competitor_id' => $tagTeamD->id],
        ],
    ]);

    $retreivedCompetitors = EventMatchData::getCompetitors($competitors);

    $this->assertCount(2, $retreivedCompetitors);

    $this->assertArrayHasKey('wrestlers', $retreivedCompetitors[0]);
    $this->assertCount(2, $retreivedCompetitors[0]['wrestlers']);
    $this->assertCollectionHas($retreivedCompetitors[0]['wrestlers']->pluck('id'), $wrestlerA->id);
    $this->assertCollectionHas($retreivedCompetitors[0]['wrestlers']->pluck('id'), $wrestlerB->id);

    $this->assertArrayHasKey('tag_teams', $retreivedCompetitors[0]);
    $this->assertCount(2, $retreivedCompetitors[0]['tag_teams']);
    $this->assertCollectionHas($retreivedCompetitors[0]['tag_teams']->pluck('id'), $tagTeamA->id);
    $this->assertCollectionHas($retreivedCompetitors[0]['tag_teams']->pluck('id'), $tagTeamB->id);

    $this->assertArrayHasKey('wrestlers', $retreivedCompetitors[1]);
    $this->assertCount(2, $retreivedCompetitors[1]['wrestlers']);
    $this->assertCollectionHas($retreivedCompetitors[1]['wrestlers']->pluck('id'), $wrestlerC->id);
    $this->assertCollectionHas($retreivedCompetitors[1]['wrestlers']->pluck('id'), $wrestlerD->id);

    $this->assertArrayHasKey('tag_teams', $retreivedCompetitors[1]);
    $this->assertCount(2, $retreivedCompetitors[1]['tag_teams']);
    $this->assertCollectionHas($retreivedCompetitors[1]['tag_teams']->pluck('id'), $tagTeamC->id);
    $this->assertCollectionHas($retreivedCompetitors[1]['tag_teams']->pluck('id'), $tagTeamD->id);
});
