<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\Factories\TagTeamFactory;
use Tests\Factories\WrestlerFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up test environment for this class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        \Event::fake();
    }

    /** @test */
    public function a_tag_team_has_a_wrestler_history()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $this->assertInstanceOf(Collection::class, $tagTeam->wrestlerHistory);
    }

    /** @test */
    public function a_bookable_tag_team_has_two_current_wrestlers()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertCount(2, $tagTeam->wrestlerHistory);
        $this->assertCount(2, $tagTeam->currentWrestlers);
    }

    /** @test */
    public function a_tag_team_can_add_wrestlers()
    {
        $tagTeam = TagTeamFactory::new()->employed()->create();

        $formerTagTeamPartner = $tagTeam->currentWrestlers->last();
        $stayingTagTeamPartner = $tagTeam->currentWrestlers->first();

        $newTagTeamPartner = WrestlerFactory::new()->employed()->create();

        $tagTeam->addWrestlers([
            $stayingTagTeamPartner->getKey(),
            $newTagTeamPartner->getKey(),
        ], $tagTeam);

        $this->assertTrue($tagTeam->currentWrestlers->contains($stayingTagTeamPartner));
        $this->assertTrue($tagTeam->currentWrestlers->contains($newTagTeamPartner));
        $this->assertFalse($tagTeam->currentWrestlers->contains($formerTagTeamPartner));
    }

    /** @test */
    public function a_tag_team_combined_weight_is_calculated_from_both_current_wrestlers_weight()
    {
        $tagTeam = TagTeamFactory::new()
            ->withExistingWrestlers([
                WrestlerFactory::new()->employed()->create(['weight' => 315]),
                WrestlerFactory::new()->employed()->create(['weight' => 340]),
            ])
            ->create();

        $this->assertEquals(655, $tagTeam->combined_weight);
    }

    /** @test */
    public function a_tag_team_without_a_current_employment_is_not_bookable()
    {
        $tagTeam = TagTeamFactory::new()->create();

        $this->assertFalse($tagTeam->isBookable());
    }

    /** @test */
    public function a_suspended_tag_team_is_not_bookable()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $this->assertFalse($tagTeam->isBookable());
    }

    /** @test */
    public function a_retired_tag_team_is_not_bookable()
    {
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $this->assertFalse($tagTeam->isBookable());
    }

    /** @test */
    public function a_suspended_tag_team_can_be_reinstated()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $tagTeam->reinstate();

        $this->assertTrue($tagTeam->isBookable());
        $this->assertNotNull($tagTeam->suspensions()->last()->ended_at);
        $this->assertNotNull($tagTeam->currentWrestlers()->first()->suspensions()->last()->ended_at);
        $this->assertNotNull($tagTeam->currentWrestlers()->last()->suspensions()->last()->ended_at);
        $this->assertTrue($tagTeam->currentWrestlers()->first()->isBookable());
        $this->assertTrue($tagTeam->currentWrestlers()->last()->isBookable());
    }

    /** @test */
    public function a_non_currently_employed_tag_team_cannot_be_reinstated()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->assertFalse($tagTeam->canBeEmployed());
    }

    /** @test */
    public function a_non_suspended_tag_team_cannot_be_reinstated()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();
        $this->assertFalse($tagTeam->canBeEmployed());

        $tagTeam = TagTeamFactory::new()->retired()->create();
        $this->assertFalse($tagTeam->canBeEmployed());
    }

    /** @test */
    public function a_tag_team_can_be_suspended()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $tagTeam->suspend();

        $this->assertFalse($tagTeam->isBookable());
        $this->assertTrue($tagTeam->suspensions()->exists());
        $this->assertNull($tagTeam->suspensions()->last()->ended_at);
        $this->assertTrue($tagTeam->currentWrestlers()->first()->suspensions()->exists());
        $this->assertTrue($tagTeam->currentWrestlers()->last()->suspensions()->exists());
        $this->assertNull($tagTeam->currentWrestlers()->first()->suspensions()->first()->ended_at);
        $this->assertNull($tagTeam->currentWrestlers()->last()->suspensions()->first()->ended_at);
        $this->assertFalse($tagTeam->currentWrestlers()->first()->isBookable());
        $this->assertFalse($tagTeam->currentWrestlers()->last()->isBookable());
    }

    /** @test */
    public function a_non_currently_employed_tag_team_cannot_be_suspended()
    {
        $tagTeam = TagTeamFactory::new()->pendingEmployment()->create();

        $this->assertFalse($tagTeam->canBeEmployed());
    }

    /** @test */
    public function a_bookable_tag_team_can_be_suspended()
    {
        $tagTeam = TagTeamFactory::new()->bookable()->create();

        $this->assertTrue($tagTeam->canBeSuspended());
    }

    /** @test */
    public function a_suspended_tag_team_cannot_be_suspended()
    {
        $tagTeam = TagTeamFactory::new()->suspended()->create();

        $this->assertFalse($tagTeam->canBeSuspended());
    }

    /** @test */
    public function a_retired_tag_team_cannot_be_suspended()
    {
        $tagTeam = TagTeamFactory::new()->retired()->create();

        $this->assertFalse($tagTeam->canBeSuspended());
    }
}
