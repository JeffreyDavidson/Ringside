<?php

namespace Tests\Unit\Models;

use App\Enums\TagTeamStatus;
use App\Models\TagTeam;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group models
 */
class TagTeamTest extends TestCase
{
    /**
     * @test
     */
    public function a_tag_team_status_gets_cast_as_a_tag_team_status_enum()
    {
        $tagTeam = TagTeam::factory()->make();

        $this->assertInstanceOf(TagTeamStatus::class, $tagTeam->status);
    }
}
