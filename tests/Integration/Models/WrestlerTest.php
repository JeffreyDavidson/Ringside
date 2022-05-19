<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Tests\Integration\Models\Concerns\EmployableContractTests;
use Tests\Integration\Models\Concerns\InjurableContractTests;
use Tests\Integration\Models\Concerns\RetirableContractTests;
use Tests\Integration\Models\Concerns\StableMemberContractTests;
use Tests\Integration\Models\Concerns\SuspendableContractTests;
use Tests\Integration\Models\Concerns\TagTeamMemberContractTests;
use Tests\TestCase;

/**
 * @group wrestlers
 */
class WrestlerTest extends TestCase
{
    use EmployableContractTests,
        InjurableContractTests,
        RetirableContractTests,
        StableMemberContractTests,
        SuspendableContractTests,
        TagTeamMemberContractTests;

    private $bookableWrestler;

    private $futureEmployedWrestler;

    private $injuredWrestler;

    private $suspendedWrestler;

    private $retiredWrestler;

    private $releasedWrestler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookableWrestler = Wrestler::factory()->bookable()->create();
        $this->futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
        $this->injuredWrestler = Wrestler::factory()->injured()->create();
        $this->suspendedWrestler = Wrestler::factory()->suspended()->create();
        $this->retiredWrestler = Wrestler::factory()->retired()->create();
        $this->releasedWrestler = Wrestler::factory()->released()->create();
    }

    protected function getEmployable()
    {
        return Wrestler::factory()->create();
    }

    protected function getInjurable()
    {
        return Wrestler::factory()->injured()->create();
    }

    protected function getRetirable()
    {
        return Wrestler::factory()->retired()->create();
    }

    protected function getStableMember()
    {
        return Wrestler::factory()->suspended()->create();
    }

    protected function getSuspendable()
    {
        return Wrestler::factory()->suspended()->create();
    }

    protected function getTagTeamMember()
    {
        return Wrestler::factory()->suspended()->create();
    }

    /**
     * @test
     */
    public function a_wrestler_has_a_name()
    {
        $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler Name']);

        $this->assertEquals('Example Wrestler Name', $wrestler->name);
    }

    /**
     * @test
     */
    public function a_wrestler_has_a_height()
    {
        $wrestler = Wrestler::factory()->create(['height' => 70]);

        $this->assertEquals('70', $wrestler->height);
    }

    /**
     * @test
     */
    public function a_wrestler_has_a_weight()
    {
        $wrestler = Wrestler::factory()->create(['weight' => 210]);

        $this->assertEquals(210, $wrestler->weight);
    }

    /**
     * @test
     */
    public function a_wrestler_has_a_hometown()
    {
        $wrestler = Wrestler::factory()->create(['hometown' => 'Los Angeles, California']);

        $this->assertEquals('Los Angeles, California', $wrestler->hometown);
    }

    /**
     * @test
     */
    public function a_wrestler_can_have_a_signature_move()
    {
        $wrestler = Wrestler::factory()->create(['signature_move' => 'Example Signature Move']);

        $this->assertEquals('Example Signature Move', $wrestler->signature_move);
    }

    /**
     * @test
     */
    public function a_wrestler_has_a_status()
    {
        $wrestler = Wrestler::factory()->create();

        $this->assertInstanceOf(WrestlerStatus::class, $wrestler->status);
    }

    /**
     * @test
     */
    public function it_can_get_bookable_wrestlers()
    {
        $bookableWrestlers = Wrestler::bookable()->get();

        $this->assertCount(1, $bookableWrestlers);
        $this->assertCollectionHas($bookableWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($bookableWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($bookableWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($bookableWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($bookableWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($bookableWrestlers, $this->releasedWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_future_employed_wrestlers()
    {
        $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

        $this->assertCount(1, $futureEmployedWrestlers);
        $this->assertCollectionHas($futureEmployedWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($futureEmployedWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($futureEmployedWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($futureEmployedWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($futureEmployedWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($futureEmployedWrestlers, $this->releasedWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_employed_wrestlers()
    {
        $employedWrestlers = Wrestler::employed()->get();

        $this->assertCount(3, $employedWrestlers);
        $this->assertCollectionHas($employedWrestlers, $this->injuredWrestler);
        $this->assertCollectionHas($employedWrestlers, $this->bookableWrestler);
        $this->assertCollectionHas($employedWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($employedWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($employedWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($employedWrestlers, $this->releasedWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_released_wrestlers()
    {
        $releasedWrestlers = Wrestler::released()->get();

        $this->assertCount(1, $releasedWrestlers);
        $this->assertCollectionHas($releasedWrestlers, $this->releasedWrestler);
        $this->assertCollectionDoesntHave($releasedWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($releasedWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($releasedWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($releasedWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($releasedWrestlers, $this->retiredWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_suspended_wrestlers()
    {
        $suspendedWrestlers = Wrestler::suspended()->get();

        $this->assertCount(1, $suspendedWrestlers);
        $this->assertCollectionHas($suspendedWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($suspendedWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($suspendedWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($suspendedWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($suspendedWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($suspendedWrestlers, $this->releasedWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_injured_wrestlers()
    {
        $injuredWrestlers = Wrestler::injured()->get();

        $this->assertCount(1, $injuredWrestlers);
        $this->assertCollectionHas($injuredWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($injuredWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($injuredWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($injuredWrestlers, $this->suspendedWrestler);
        $this->assertCollectionDoesntHave($injuredWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($injuredWrestlers, $this->releasedWrestler);
    }

    /**
     * @test
     */
    public function it_can_get_retired_wrestlers()
    {
        $retiredWrestlers = Wrestler::retired()->get();

        $this->assertCount(1, $retiredWrestlers);
        $this->assertCollectionHas($retiredWrestlers, $this->retiredWrestler);
        $this->assertCollectionDoesntHave($retiredWrestlers, $this->futureEmployedWrestler);
        $this->assertCollectionDoesntHave($retiredWrestlers, $this->bookableWrestler);
        $this->assertCollectionDoesntHave($retiredWrestlers, $this->injuredWrestler);
        $this->assertCollectionDoesntHave($retiredWrestlers, $this->suspendedWrestler);
    }
}
