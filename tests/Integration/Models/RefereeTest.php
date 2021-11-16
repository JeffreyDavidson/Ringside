<?php

namespace Tests\Integration\Models;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Tests\TestCase;

/**
 * @group referees
 */
class RefereeTest extends TestCase
{
    use Concerns\EmployableContractTests,
        Concerns\InjurableContractTests,
        Concerns\RetirableContractTests,
        Concerns\SuspendableContractTests;

    private $futureEmployedReferee;
    private $bookableReferee;
    private $injuredReferee;
    private $suspendedReferee;
    private $retiredReferee;
    private $releasedReferee;

    public function setUp(): void
    {
        parent::setUp();

        $this->futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
        $this->bookableReferee = Referee::factory()->bookable()->create();
        $this->injuredReferee = Referee::factory()->injured()->create();
        $this->suspendedReferee = Referee::factory()->suspended()->create();
        $this->retiredReferee = Referee::factory()->retired()->create();
        $this->releasedReferee = Referee::factory()->released()->create();
    }

    protected function getEmployable()
    {
        return Referee::factory()->create();
    }

    protected function getInjurable()
    {
        return Referee::factory()->injured()->create();
    }

    protected function getRetirable()
    {
        return Referee::factory()->retired()->create();
    }

    protected function getSuspendable()
    {
        return Referee::factory()->suspended()->create();
    }

    /**
     * @test
     */
    public function a_referee_has_a_first_name()
    {
        $referee = Referee::factory()->create(['first_name' => 'John']);

        $this->assertEquals('John', $referee->first_name);
    }

    /**
     * @test
     */
    public function a_referee_has_a_last_name()
    {
        $referee = Referee::factory()->create(['last_name' => 'Smith']);

        $this->assertEquals('Smith', $referee->last_name);
    }

    /**
     * @test
     */
    public function a_referee_has_a_status()
    {
        $referee = Referee::factory()->create();

        $this->assertInstanceOf(RefereeStatus::class, $referee->status);
    }

    /**
     * @test
     */
    public function it_can_get_bookable_referees()
    {
        $bookableReferees = Referee::bookable()->get();

        $this->assertCount(1, $bookableReferees);
        $this->assertTrue($bookableReferees->contains($this->bookableReferee));
        $this->assertFalse($bookableReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($bookableReferees->contains($this->injuredReferee));
        $this->assertFalse($bookableReferees->contains($this->suspendedReferee));
        $this->assertFalse($bookableReferees->contains($this->retiredReferee));
        $this->assertFalse($bookableReferees->contains($this->releasedReferee));
    }

    /**
     * @test
     */
    public function it_can_get_future_employed_referees()
    {
        $futureEmployedReferees = Referee::futureEmployed()->get();

        $this->assertCount(1, $futureEmployedReferees);
        $this->assertTrue($futureEmployedReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($futureEmployedReferees->contains($this->bookableReferee));
        $this->assertFalse($futureEmployedReferees->contains($this->injuredReferee));
        $this->assertFalse($futureEmployedReferees->contains($this->suspendedReferee));
        $this->assertFalse($futureEmployedReferees->contains($this->retiredReferee));
        $this->assertFalse($futureEmployedReferees->contains($this->releasedReferee));
    }

    /**
     * @test
     */
    public function it_can_get_employed_referees()
    {
        $employedReferees = Referee::employed()->get();

        $this->assertCount(3, $employedReferees);
        $this->assertTrue($employedReferees->contains($this->injuredReferee));
        $this->assertTrue($employedReferees->contains($this->bookableReferee));
        $this->assertTrue($employedReferees->contains($this->suspendedReferee));
        $this->assertFalse($employedReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($employedReferees->contains($this->retiredReferee));
        $this->assertFalse($employedReferees->contains($this->releasedReferee));
    }

    /**
     * @test
     */
    public function it_can_get_released_referees()
    {
        $releasedReferees = Referee::released()->get();

        $this->assertCount(1, $releasedReferees);
        $this->assertTrue($releasedReferees->contains($this->releasedReferee));
        $this->assertFalse($releasedReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($releasedReferees->contains($this->bookableReferee));
        $this->assertFalse($releasedReferees->contains($this->injuredReferee));
        $this->assertFalse($releasedReferees->contains($this->suspendedReferee));
        $this->assertFalse($releasedReferees->contains($this->retiredReferee));
    }

    /**
     * @test
     */
    public function it_can_get_suspended_referees()
    {
        $suspendedReferees = Referee::suspended()->get();

        $this->assertCount(1, $suspendedReferees);
        $this->assertTrue($suspendedReferees->contains($this->suspendedReferee));
        $this->assertFalse($suspendedReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($suspendedReferees->contains($this->bookableReferee));
        $this->assertFalse($suspendedReferees->contains($this->injuredReferee));
        $this->assertFalse($suspendedReferees->contains($this->retiredReferee));
        $this->assertFalse($suspendedReferees->contains($this->releasedReferee));
    }

    /**
     * @test
     */
    public function it_can_get_injured_referees()
    {
        $injuredReferees = Referee::injured()->get();

        $this->assertCount(1, $injuredReferees);
        $this->assertTrue($injuredReferees->contains($this->injuredReferee));
        $this->assertFalse($injuredReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($injuredReferees->contains($this->bookableReferee));
        $this->assertFalse($injuredReferees->contains($this->suspendedReferee));
        $this->assertFalse($injuredReferees->contains($this->retiredReferee));
        $this->assertFalse($injuredReferees->contains($this->releasedReferee));
    }

    /**
     * @test
     */
    public function it_can_get_retired_referees()
    {
        $retiredReferees = Referee::retired()->get();

        $this->assertCount(1, $retiredReferees);
        $this->assertTrue($retiredReferees->contains($this->retiredReferee));
        $this->assertFalse($retiredReferees->contains($this->futureEmployedReferee));
        $this->assertFalse($retiredReferees->contains($this->bookableReferee));
        $this->assertFalse($retiredReferees->contains($this->injuredReferee));
        $this->assertFalse($retiredReferees->contains($this->suspendedReferee));
    }
}
