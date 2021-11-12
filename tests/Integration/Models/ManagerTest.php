<?php

namespace Tests\Integration\Models;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 */
class ManagerTest extends TestCase
{
    use RefreshDatabase,
        Concerns\EmployableContractTests,
        Concerns\InjurableContractTests,
        Concerns\RetirableContractTests,
        Concerns\SuspendableContractTests;

    private $futureEmployedManager;
    private $availableManager;
    private $injuredManager;
    private $suspendedManager;
    private $retiredManager;
    private $releasedManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $this->availableManager = Manager::factory()->available()->create();
        $this->injuredManager = Manager::factory()->injured()->create();
        $this->suspendedManager = Manager::factory()->suspended()->create();
        $this->retiredManager = Manager::factory()->retired()->create();
        $this->releasedManager = Manager::factory()->released()->create();
    }

    protected function getEmployable()
    {
        return Manager::factory()->create();
    }

    protected function getInjurable()
    {
        return Manager::factory()->injured()->create();
    }

    protected function getRetirable()
    {
        return Manager::factory()->retired()->create();
    }

    protected function getSuspendable()
    {
        return Manager::factory()->suspended()->create();
    }

    /**
     * @test
     */
    public function a_manager_has_a_first_name()
    {
        $manager = Manager::factory()->create(['first_name' => 'John']);

        $this->assertEquals('John', $manager->first_name);
    }

    /**
     * @test
     */
    public function a_manager_has_a_last_name()
    {
        $manager = Manager::factory()->create(['last_name' => 'Smith']);

        $this->assertEquals('Smith', $manager->last_name);
    }

    /**
     * @test
     */
    public function a_manager_has_a_status()
    {
        $manager = Manager::factory()->create();

        $this->assertInstanceOf(ManagerStatus::class, $manager->status);
    }

    /**
     * @test
     */
    public function it_can_get_available_managers()
    {
        $availableManagers = Manager::available()->get();

        $this->assertCount(1, $availableManagers);
        $this->assertTrue($availableManagers->contains($this->availableManager));
        $this->assertFalse($availableManagers->contains($this->futureEmployedManager));
        $this->assertFalse($availableManagers->contains($this->injuredManager));
        $this->assertFalse($availableManagers->contains($this->suspendedManager));
        $this->assertFalse($availableManagers->contains($this->retiredManager));
        $this->assertFalse($availableManagers->contains($this->releasedManager));
    }

    /**
     * @test
     */
    public function it_can_get_future_employed_managers()
    {
        $futureEmployedManagers = Manager::futureEmployed()->get();

        $this->assertCount(1, $futureEmployedManagers);
        $this->assertTrue($futureEmployedManagers->contains($this->futureEmployedManager));
        $this->assertFalse($futureEmployedManagers->contains($this->availableManager));
        $this->assertFalse($futureEmployedManagers->contains($this->injuredManager));
        $this->assertFalse($futureEmployedManagers->contains($this->suspendedManager));
        $this->assertFalse($futureEmployedManagers->contains($this->retiredManager));
        $this->assertFalse($futureEmployedManagers->contains($this->releasedManager));
    }

    /**
     * @test
     */
    public function it_can_get_employed_managers()
    {
        $employedManagers = Manager::employed()->get();

        $this->assertCount(3, $employedManagers);
        $this->assertTrue($employedManagers->contains($this->injuredManager));
        $this->assertTrue($employedManagers->contains($this->availableManager));
        $this->assertTrue($employedManagers->contains($this->suspendedManager));
        $this->assertFalse($employedManagers->contains($this->futureEmployedManager));
        $this->assertFalse($employedManagers->contains($this->retiredManager));
        $this->assertFalse($employedManagers->contains($this->releasedManager));
    }

    /**
     * @test
     */
    public function it_can_get_released_managers()
    {
        $releasedManagers = Manager::released()->get();

        $this->assertCount(1, $releasedManagers);
        $this->assertTrue($releasedManagers->contains($this->releasedManager));
        $this->assertFalse($releasedManagers->contains($this->futureEmployedManager));
        $this->assertFalse($releasedManagers->contains($this->availableManager));
        $this->assertFalse($releasedManagers->contains($this->injuredManager));
        $this->assertFalse($releasedManagers->contains($this->suspendedManager));
        $this->assertFalse($releasedManagers->contains($this->retiredManager));
    }

    /**
     * @test
     */
    public function it_can_get_suspended_managers()
    {
        $suspendedManagers = Manager::suspended()->get();

        $this->assertCount(1, $suspendedManagers);
        $this->assertTrue($suspendedManagers->contains($this->suspendedManager));
        $this->assertFalse($suspendedManagers->contains($this->futureEmployedManager));
        $this->assertFalse($suspendedManagers->contains($this->availableManager));
        $this->assertFalse($suspendedManagers->contains($this->injuredManager));
        $this->assertFalse($suspendedManagers->contains($this->retiredManager));
        $this->assertFalse($suspendedManagers->contains($this->releasedManager));
    }

    /**
     * @test
     */
    public function it_can_get_injured_managers()
    {
        $injuredManagers = Manager::injured()->get();

        $this->assertCount(1, $injuredManagers);
        $this->assertTrue($injuredManagers->contains($this->injuredManager));
        $this->assertFalse($injuredManagers->contains($this->futureEmployedManager));
        $this->assertFalse($injuredManagers->contains($this->availableManager));
        $this->assertFalse($injuredManagers->contains($this->suspendedManager));
        $this->assertFalse($injuredManagers->contains($this->retiredManager));
        $this->assertFalse($injuredManagers->contains($this->releasedManager));
    }

    /**
     * @test
     */
    public function it_can_get_retired_managers()
    {
        $retiredManagers = Manager::retired()->get();

        $this->assertCount(1, $retiredManagers);
        $this->assertTrue($retiredManagers->contains($this->retiredManager));
        $this->assertFalse($retiredManagers->contains($this->futureEmployedManager));
        $this->assertFalse($retiredManagers->contains($this->availableManager));
        $this->assertFalse($retiredManagers->contains($this->injuredManager));
        $this->assertFalse($retiredManagers->contains($this->suspendedManager));
    }
}
