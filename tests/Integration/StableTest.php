<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        Event::fake();
    }

    /** @test */
    public function a_stable_has_a_name()
    {
        $stable = StableFactory::new()->create(['name' => 'Example Stable Name']);

        $this->assertEquals('Example Stable Name', $stable->name);
    }

    /** @test */
    public function a_stable_can_be_employed()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $stable = StableFactory::new()->create();

        $stable->employ();

        $this->assertEquals($now->toDateTimeString(), $stable->currentEmployment->started_at);
    }

    /** @test */
    public function a_stable_can_add_wrestlers()
    {
        $stable = StableFactory::new()->create();
        $wrestler = factory(Wrestler::class)->create();

        $stable->addWrestlers($wrestler);

        $this->assertTrue($stable->currentMembers->contains($wrestler));
        $this->assertEquals(now()->toDateTimeString(), $stable->currentWrestlers->first()->pivot->joined_at);
    }

    /** @test */
    public function a_stable_can_add_tag_teams()
    {
        $stable = StableFactory::new()->create();
        $tagTeam = factory(TagTeam::class)->create();

        $stable->addTagTeams($tagTeam);

        $this->assertTrue($stable->currentMembers->contains($tagTeam));
        $this->assertEquals(now()->toDateTimeString(), $stable->currentTagTeams->first()->pivot->joined_at);
    }

    /** @test */
    public function a_stable_can_add_managers()
    {
        $stable = StableFactory::new()->create();
        $manager = ManagerFactory::new()->create();

        $stable->addManagers($manager);

        $this->assertTrue($stable->members->contains($manager));
        $this->assertEquals(now()->toDateTimeString(), $stable->currentManagers->first()->pivot->joined_at);
    }

    /** @test */
    public function a_stable_can_disassemble()
    {
        $stable = StableFactory::new()->create();
        $wrestlers = factory(Wrestler::class, 3)->create();
        $stable->addWrestlers($wrestlers);

        $this->assertTrue($stable->currentMembers->isEmpty());
        $this->assertCount(2, $stable->previousMembers);
    }
}
