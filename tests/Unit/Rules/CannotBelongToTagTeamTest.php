<?php

namespace Tests\Unit\Rules;

use App\Models\TagTeam;
use App\Rules\CannotBelongToTagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class CannotBelongToTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_belong_to_multiple_bookable_tag_teams()
    {
        $tagTeam = TagTeam::factory()->bookable()->create();

        $this->assertFalse((new CannotBelongToTagTeam(now()->toDateTimeString()))->passes(null, $tagTeam->currentWrestlers->first()->id));
    }
}
