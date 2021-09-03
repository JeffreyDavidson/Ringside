<?php

namespace Tests\Integration\Models;

use App\Models\TagTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group tagteams
 */
class TagTeamTest extends TestCase
{
    use RefreshDatabase,
        Concerns\EmployableContractTests,
        Concerns\RetirableContractTests,
        Concerns\StableMemberContractTests,
        Concerns\SuspendableContractTests;

    private $futureEmployedTagTeam;
    private $bookableTagTeam;
    private $suspendedTagTeam;
    private $retiredTagTeam;
    private $releasedTagTeam;

    public function setUp(): void
    {
        parent::setUp();

        $this->futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
        $this->bookableTagTeam = TagTeam::factory()->bookable()->create();
        $this->suspendedTagTeam = TagTeam::factory()->suspended()->create();
        $this->retiredTagTeam = TagTeam::factory()->retired()->create();
        $this->releasedTagTeam = TagTeam::factory()->released()->create();
    }

    protected function getEmployable()
    {
        return TagTeam::factory()->create();
    }

    protected function getRetirable()
    {
        return TagTeam::factory()->retired()->create();
    }

    protected function getStableMember()
    {
        return TagTeam::factory()->bookable()->create();
    }

    protected function getSuspendable()
    {
        return TagTeam::factory()->suspended()->create();
    }

    /**
     * @test
     */
    public function a_tag_team_has_a_name()
    {
        $tagTeam = TagTeam::factory()->create(['name' => 'Example Tag Team Name']);

        $this->assertEquals('Example Tag Team Name', $tagTeam->name);
    }

    /**
     * @test
     */
    public function a_tag_team_can_have_a_signature_move()
    {
        $tagTeam = TagTeam::factory()->create(['signature_move' => 'Example Signature Move']);

        $this->assertEquals('Example Signature Move', $tagTeam->signature_move);
    }

    /**
     * @test
     */
    public function a_tag_team_has_a_status()
    {
        $tagTeam = TagTeam::factory()->create();
        $tagTeam->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $tagTeam->getRawOriginal('status'));
    }

    /**
     * @test
     */
    public function it_can_get_bookable_tag_teams()
    {
        $bookableTagTeams = TagTeam::bookable()->get();

        $this->assertCount(1, $bookableTagTeams);
        $this->assertCollectionHas($bookableTagTeams, $this->bookableTagTeam);
        $this->assertCollectionDoesntHave($bookableTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($bookableTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($bookableTagTeams, $this->retiredTagTeam);
        $this->assertCollectionDoesntHave($bookableTagTeams, $this->releasedTagTeam);
    }

    /**
     * @test
     */
    public function it_can_get_future_employed_tag_teams()
    {
        $futureEmployedTagTeams = TagTeam::futureEmployed()->get();

        $this->assertCount(1, $futureEmployedTagTeams);
        $this->assertCollectionHas($futureEmployedTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($futureEmployedTagTeams, $this->bookableTagTeam);
        $this->assertCollectionDoesntHave($futureEmployedTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($futureEmployedTagTeams, $this->retiredTagTeam);
        $this->assertCollectionDoesntHave($futureEmployedTagTeams, $this->releasedTagTeam);
    }

    /**
     * @test
     */
    public function it_can_get_employed_tag_teams()
    {
        $employedTagTeams = TagTeam::employed()->get();

        $this->assertCount(2, $employedTagTeams);
        $this->assertCollectionHas($employedTagTeams, $this->bookableTagTeam);
        $this->assertCollectionHas($employedTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($employedTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($employedTagTeams, $this->retiredTagTeam);
        $this->assertCollectionDoesntHave($employedTagTeams, $this->releasedTagTeam);
    }

    /**
     * @test
     */
    public function it_can_get_released_tag_teams()
    {
        $releasedTagTeams = TagTeam::released()->get();

        $this->assertCount(1, $releasedTagTeams);
        $this->assertCollectionHas($releasedTagTeams, $this->releasedTagTeam);
        $this->assertCollectionDoesntHave($releasedTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($releasedTagTeams, $this->bookableTagTeam);
        $this->assertCollectionDoesntHave($releasedTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($releasedTagTeams, $this->retiredTagTeam);
    }

    /**
     * @test
     */
    public function it_can_get_suspended_tag_teams()
    {
        $suspendedTagTeams = TagTeam::suspended()->get();

        $this->assertCount(1, $suspendedTagTeams);
        $this->assertCollectionHas($suspendedTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($suspendedTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($suspendedTagTeams, $this->bookableTagTeam);
        $this->assertCollectionDoesntHave($suspendedTagTeams, $this->retiredTagTeam);
        $this->assertCollectionDoesntHave($suspendedTagTeams, $this->releasedTagTeam);
    }

    /**
     * @test
     */
    public function it_can_get_retired_tag_teams()
    {
        $retiredTagTeams = TagTeam::retired()->get();

        $this->assertCount(1, $retiredTagTeams);
        $this->assertCollectionHas($retiredTagTeams, $this->retiredTagTeam);
        $this->assertCollectionDoesntHave($retiredTagTeams, $this->futureEmployedTagTeam);
        $this->assertCollectionDoesntHave($retiredTagTeams, $this->bookableTagTeam);
        $this->assertCollectionDoesntHave($retiredTagTeams, $this->suspendedTagTeam);
        $this->assertCollectionDoesntHave($retiredTagTeams, $this->releasedTagTeam);
    }
}
