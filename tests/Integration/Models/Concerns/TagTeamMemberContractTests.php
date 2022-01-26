<?php

namespace Tests\Integration\Models\Concerns;

use App\Models\TagTeam;

trait TagTeamMemberContractTests
{
    abstract protected function getTagTeamMember();

    /**
     * @test
     */
    public function a_tag_team_member_can_only_be_on_one_current_tag_team()
    {
        $tagTeamMember = $this->getTagTeamMember();

        TagTeam::factory()
            ->hasAttached($tagTeamMember, ['joined_at' => now()->toDateTimeString()])
            ->create();

        $this->assertInstanceOf(TagTeam::class, $tagTeamMember->currentTagTeam());
    }
}
