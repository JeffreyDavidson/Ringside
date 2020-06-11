<?php

namespace Tests\Unit\Rules;

use App\Rules\CannotBelongToTagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TagTeamFactory;
use Tests\TestCase;

class CannotBelongToTagTeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_wrestler_cannot_belong_to_multiple_bookable_tag_teams()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertFalse((new CannotBelongToTagTeam(now()->toDateTimeString()))->passes(null, $tagTeam->currentWrestlers->first()->id));
    }
}
