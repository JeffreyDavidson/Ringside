<?php

namespace Tests\Integration\Rules;

use App\Rules\StableHasEnoughMembers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class StableHasEnoughMembersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function wrestlers_and_tag_teams_can_be_empty_if_start_date_is_null()
    {
        $this->assertTrue(
            (new StableHasEnoughMembers(null, []))->passes(null, [])
        );
    }

    /**
     * @test
     */
    public function wrestlers_and_tag_teams_can_contain_any_amount_of_ids_each_if_start_date_is_null()
    {
        $this->assertTrue(
            (new StableHasEnoughMembers(null, [1, 2, 3]))->passes(null, [])
        );

        $this->assertTrue(
            (new StableHasEnoughMembers(null, []))->passes(null, [1, 2, 3])
        );
    }

    /**
     * @test
     */
    public function wrestlers_must_have_at_least_3_wrestler_ids_if_tag_teams_is_empty()
    {
        $this->assertFalse(
            (new StableHasEnoughMembers(now()->toDateTimeString(), []))->passes(null, [1, 2])
        );

        $this->assertTrue(
            (new StableHasEnoughMembers(now()->toDateTimeString(), []))->passes(null, [1, 2, 3])
        );
    }

    /**
     * @test
     */
    public function tag_teams_must_have_at_least_2_wrestler_ids_if_tag_teams_is_empty()
    {
        $this->assertFalse(
            (new StableHasEnoughMembers(now()->toDateTimeString(), [1]))->passes(null, [])
        );

        $this->assertTrue(
            (new StableHasEnoughMembers(now()->toDateTimeString(), [1, 2]))->passes(null, [])
        );
    }

    /**
     * @test
     */
    public function wrestlers_must_have_at_least_1_wrestler_id_if_tag_teams_is_exactly_one_tag_team()
    {
        $this->assertTrue(
            (new StableHasEnoughMembers(now()->toDateTimeString(), [1]))->passes(null, [1])
        );
    }
}
