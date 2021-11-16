<?php

namespace Tests\Integration\Rules;

use App\Rules\StableHasEnoughMembers;
use Tests\TestCase;

/**
 * @group rules
 */
class StableHasEnoughMembersTest extends TestCase
{
    /**
     * @test
     */
    public function wrestlers_must_have_at_least_3_wrestler_ids_if_tag_teams_is_empty()
    {
        $this->assertFalse(
            (new StableHasEnoughMembers([], [1, 2]))->passes(null, null)
        );

        $this->assertTrue(
            (new StableHasEnoughMembers([], [1, 2, 3]))->passes(null, null)
        );
    }

    /**
     * @test
     */
    public function tag_teams_must_have_at_least_2_tag_team_ids_if_wrestlers_is_empty()
    {
        $this->assertFalse(
            (new StableHasEnoughMembers([], [1]))->passes(null, null)
        );

        $this->assertTrue(
            (new StableHasEnoughMembers([1, 2], []))->passes(null, null)
        );
    }

    /**
     * @test
     */
    public function wrestlers_must_have_at_least_1_wrestler_id_if_tag_teams_is_exactly_one_tag_team()
    {
        $result = (new StableHasEnoughMembers([1], [1]));

        $this->assertTrue($result->passes(null, null));
    }
}
