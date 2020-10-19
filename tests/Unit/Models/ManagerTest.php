<?php

namespace Tests\Unit\Models;

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
use App\Exceptions\CannotBeReleasedException;
use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group models
 */
class ManagerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_manager_has_a_first_name()
    {
        $manager = new Manager(['first_name' => 'John']);

        $this->assertEquals('John', $manager->first_name);
    }

    /** @test */
    public function a_manager_has_a_last_name()
    {
        $manager = new Manager(['last_name' => 'Smith']);

        $this->assertEquals('Smith', $manager->last_name);
    }

    /** @test */
    public function a_manager_has_a_status()
    {
        $manager = new Manager();
        $manager->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $manager->getRawOriginal('status'));
    }

    /** @test */
    public function a_manager_status_is_a_enum()
    {
        $manager = new Manager();

        $this->assertInstanceOf(ManagerStatus::class, $manager->status);
    }

    /** @test */
    public function a_manager_uses_has_a_full_name_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasFullName', Manager::class);
    }

    /** @test */
    public function a_manager_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Manager::class);
    }

    /** @test */
    public function an_available_manager_can_be_suspended()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $manager->suspend();

        $this->assertEquals('suspended', $manager->status);
        $this->assertCount(1, $manager->suspensions);
        $this->assertNull($manager->currentSuspension->ended_at);
        $this->assertEquals($now->toDateTimeString(), $manager->currentSuspension->started_at);
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_suspended()
    {
        $this->expectException(CannotBeSuspendedException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->suspend();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_suspended()
    {
        $this->expectException(CannotBeSuspendedException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->suspend();
    }

    /** @test */
    public function a_retired_manager_cannot_be_suspended()
    {
        $this->expectException(CannotBeSuspendedException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->suspend();
    }

    /** @test */
    public function an_suspended_manager_cannot_be_suspended()
    {
        $this->expectException(CannotBeSuspendedException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->suspend();
    }

    /** @test */
    public function an_available_manager_cannot_be_reinstated()
    {
        $this->expectException(CannotBeReinstatedException::class);

        $manager = Manager::factory()->available()->create();

        $manager->reinstate();
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_reinstated()
    {
        $this->expectException(CannotBeReinstatedException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->reinstate();
    }

    /** @test */
    public function an_injured_manager_cannot_be_reinstated()
    {
        $this->expectException(CannotBeReinstatedException::class);

        $manager = Manager::factory()->injured()->create();

        $manager->reinstate();
    }

    /** @test */
    public function a_retired_manager_cannot_be_reinstated()
    {
        $this->expectException(CannotBeReinstatedException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->reinstate();
    }

    /** @test */
    public function a_suspended_manager_can_be_reinstated()
    {
        $manager = Manager::factory()->suspended()->create();

        $manager->reinstate();

        $this->assertEquals('available', $manager->status);
        $this->assertNotNull($manager->previousSuspension->ended_at);
    }

    /** @test */
    public function a_manager_with_a_suspension_is_suspended()
    {
        $manager = Manager::factory()->suspended()->create();

        $this->assertTrue($manager->isSuspended());
    }

    /** @test */
    public function a_manager_can_be_suspended_multiple_times()
    {
        $manager = Manager::factory()->suspended()->create();

        $manager->reinstate();
        $manager->suspend();

        $this->assertCount(1, $manager->previousSuspensions);
    }

    /** @test */
    public function a_manager_with_a_retirement_is_retired()
    {
        $manager = Manager::factory()->retired()->create();

        $this->assertTrue($manager->isRetired());
    }

    /** @test */
    public function it_can_get_retired_models()
    {
        $retiredManager = Manager::factory()->retired()->create();
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();

        $retiredModels = Manager::retired()->get();

        $this->assertCount(1, $retiredModels);
        $this->assertTrue($retiredModels->contains($retiredManager));
        $this->assertFalse($retiredModels->contains($futureEmployedManager));
        $this->assertFalse($retiredModels->contains($availableManager));
        $this->assertFalse($retiredModels->contains($injuredManager));
        $this->assertFalse($retiredModels->contains($suspendedManager));
    }

    /** @test */
    public function an_available_manager_can_be_retired()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $manager->retire();

        $this->assertEquals('retired', $manager->status);
        $this->assertCount(1, $manager->retirements);
        $this->assertEquals($now->toDateTimeString(), $manager->currentRetirement->started_at);
    }

    /** @test */
    public function a_suspended_manager_can_be_retired()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->suspended()->create();

        $this->assertNull($manager->currentSuspension->ended_at);

        $manager->retire();

        $this->assertEquals('retired', $manager->status);
        $this->assertCount(1, $manager->retirements);
        $this->assertNotNull($manager->previousSuspension->ended_at);
        $this->assertEquals($now->toDateTimeString(), $manager->currentRetirement->started_at);
    }

    /** @test */
    public function an_injured_manager_can_be_retired()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->injured()->create();

        $this->assertNull($manager->injuries()->latest()->first()->ended_at);

        $manager->retire();

        $this->assertEquals('retired', $manager->status);
        $this->assertCount(1, $manager->retirements);
        $this->assertNotNull($manager->previousInjury->ended_at);
        $this->assertEquals($now->toDateTimeString(), $manager->currentRetirement->started_at);
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_retired()
    {
        $this->expectException(CannotBeRetiredException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->retire();
    }

    /** @test */
    public function a_retired_manager_cannot_be_retired()
    {
        $this->expectException(CannotBeRetiredException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->retire();
    }

    /** @test */
    public function a_retired_manager_can_be_unretired()
    {
        $manager = Manager::factory()->retired()->create();

        $manager->unretire();

        $this->assertEquals('available', $manager->status);
        $this->assertNotNull($manager->previousRetirement->ended_at);
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_unretired()
    {
        $this->expectException(CannotBeUnretiredException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->unretire();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_unretired()
    {
        $this->expectException(CannotBeUnretiredException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->unretire();
    }

    /** @test */
    public function an_injured_manager_cannot_be_unretired()
    {
        $this->expectException(CannotBeUnretiredException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->unretire();
    }

    /** @test */
    public function an_available_manager_cannot_be_unretired()
    {
        $this->expectException(CannotBeUnretiredException::class);

        $manager = Manager::factory()->available()->create();

        $manager->unretire();
    }

    /** @test */
    public function a_manager_that_retires_and_unretires_has_a_previous_retirement()
    {
        $manager = Manager::factory()->available()->create();
        $manager->retire();
        $manager->unretire();

        $this->assertCount(1, $manager->previousRetirements);
    }

    /** @test */
    public function an_available_manager_can_be_injured()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $manager->injure();

        $this->assertEquals('injured', $manager->status);
        $this->assertCount(1, $manager->injuries);
        $this->assertNull($manager->currentInjury->ended_at);
        $this->assertEquals($now->toDateTimeString(), $manager->currentInjury->started_at);
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_injured()
    {
        $this->expectException(CannotBeInjuredException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->injure();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_injured()
    {
        $this->expectException(CannotBeInjuredException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->injure();
    }

    /** @test */
    public function a_retired_manager_cannot_be_injured()
    {
        $this->expectException(CannotBeInjuredException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->injure();
    }

    /** @test */
    public function an_injured_manager_cannot_be_injured()
    {
        $this->expectException(CannotBeInjuredException::class);

        $manager = Manager::factory()->injured()->create();

        $manager->injure();
    }

    /** @test */
    public function an_available_manager_cannot_be_cleared_from_an_injury()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $manager = Manager::factory()->available()->create();

        $manager->clearFromInjury();
    }

    /** @test */
    public function a_future_employment_manager_cannot_be_cleared_from_an_injury()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->clearFromInjury();
    }

    /** @test */
    public function a_suspended_manager_cannot_be_cleared_from_an_injury()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $manager = Manager::factory()->suspended()->create();

        $manager->clearFromInjury();
    }

    /** @test */
    public function a_retired_manager_cannot_be_cleared_from_injury()
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->clearFromInjury();
    }

    /** @test */
    public function an_injured_manager_can_be_cleared_from_an_injury()
    {
        $manager = Manager::factory()->injured()->create();

        $manager->clearFromInjury();

        $this->assertEquals(ManagerStatus::AVAILABLE, $manager->status);
        $this->assertNotNull($manager->previousInjury->ended_at);
    }

    /** @test */
    public function a_manager_with_an_injury_is_injured()
    {
        $manager = Manager::factory()->injured()->create();

        $this->assertTrue($manager->isInjured());
    }

    /** @test */
    public function a_manager_can_be_injured_multiple_times()
    {
        $manager = Manager::factory()->injured()->create();

        $manager->clearFromInjury();
        $manager->injure();

        $this->assertCount(1, $manager->previousInjuries);
    }

    /** @test */
    public function a_single_roster_member_can_be_employed_default_to_now()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->create();

        $manager->employ();

        $this->assertCount(1, $manager->employments);
        $this->assertEquals($now->toDateTimeString(), $manager->currentEmployment->started_at);
    }

    /** @test */
    public function a_single_roster_member_can_be_employed_at_start_date()
    {
        $yesterday = Carbon::yesterday();
        Carbon::setTestNow($yesterday);

        $manager = Manager::factory()->create();

        $manager->employ($yesterday);

        $this->assertEquals($yesterday->toDateTimeString(), $manager->currentEmployment->started_at);
    }

    /** @test */
    public function a_single_roster_member_with_an_employment_in_the_future_can_be_employed_at_start_date()
    {
        $today = Carbon::today();
        Carbon::setTestNow($today);

        $manager = Manager::factory()->create();
        $manager->employments()->create(['started_at' => Carbon::tomorrow()]);

        $manager->employ($today);

        $this->assertEquals($today->toDateTimeString(), $manager->currentEmployment->started_at);
    }

    /** @test */
    public function a_available_single_roster_member_can_be_fired_default_to_now()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->available()->create();

        $this->assertNull($manager->currentEmployment->ended_at);

        $manager->release();

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $manager->previousEmployment->ended_at);
    }

    /** @test */
    public function a_available_single_roster_member_can_be_fired_at_start_date()
    {
        $yesterday = Carbon::yesterday();
        Carbon::setTestNow($yesterday);

        $manager = Manager::factory()->available()->create();

        $this->assertNull($manager->currentEmployment->ended_at);

        $manager->release($yesterday);

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($yesterday->toDateTimeString(), $manager->previousEmployment->ended_at);
    }

    /** @test */
    public function an_injured_single_roster_member_can_be_fired_default_to_now()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->injured()->create();

        $this->assertNull($manager->currentInjury->ended_at);

        $manager->release();

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $manager->previousEmployment->ended_at);
        $this->assertNotNull($manager->previousInjury->ended_at);
    }

    /** @test */
    public function a_suspended_single_roster_member_can_be_fired_default_to_now()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->suspended()->create();

        $this->assertNull($manager->currentSuspension->ended_at);

        $manager->release();

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $manager->previousEmployment->ended_at);
        $this->assertNotNull($manager->previousSuspension->ended_at);
    }

    /** @test */
    public function a_future_employment_single_roster_member_cannot_be_fired()
    {
        $this->expectException(CannotBeReleasedException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->release();
    }

    /** @test */
    public function a_retired_single_roster_member_cannot_be_fired()
    {
        $this->expectException(CannotBeReleasedException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->release();
    }

    /** @test */
    public function a_manager_with_an_employment_now_or_in_the_past_is_employed()
    {
        $manager = Manager::factory()->create();
        $manager->employments()->create(['started_at' => Carbon::now()]);

        $this->assertTrue($manager->isCurrentlyEmployed());
    }

    /** @test */
    public function it_can_get_available_managers()
    {
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $availableManagers = Manager::available()->get();

        $this->assertCount(1, $availableManagers);
        $this->assertTrue($availableManagers->contains($availableManager));
        $this->assertFalse($availableManagers->contains($futureEmployedManager));
        $this->assertFalse($availableManagers->contains($injuredManager));
        $this->assertFalse($availableManagers->contains($suspendedManager));
        $this->assertFalse($availableManagers->contains($retiredManager));
        $this->assertFalse($availableManagers->contains($releasedManager));
    }

    /** @test */
    public function it_can_get_future_employmed_managers()
    {
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $futureEmployedManagers = Manager::futureEmployed()->get();

        $this->assertCount(1, $futureEmployedManagers);
        $this->assertTrue($futureEmployedManagers->contains($futureEmployedManager));
        $this->assertFalse($futureEmployedManagers->contains($availableManager));
        $this->assertFalse($futureEmployedManagers->contains($injuredManager));
        $this->assertFalse($futureEmployedManagers->contains($suspendedManager));
        $this->assertFalse($futureEmployedManagers->contains($retiredManager));
        $this->assertFalse($futureEmployedManagers->contains($releasedManager));
    }

    /** @test */
    public function it_can_get_employed_managers()
    {
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $employedManagers = Manager::employed()->get();

        $this->assertCount(3, $employedManagers);
        $this->assertTrue($employedManagers->contains($injuredManager));
        $this->assertTrue($employedManagers->contains($availableManager));
        $this->assertTrue($employedManagers->contains($suspendedManager));
        $this->assertFalse($employedManagers->contains($futureEmployedManager));
        $this->assertFalse($employedManagers->contains($retiredManager));
        $this->assertFalse($employedManagers->contains($releasedManager));
    }

    /** @test */
    public function it_can_get_released_managers()
    {
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $releasedManagers = Manager::released()->get();

        $this->assertCount(3, $releasedManagers);
        $this->assertTrue($releasedManagers->contains($releasedManager));
        $this->assertFalse($releasedManagers->contains($futureEmployedManager));
        $this->assertFalse($releasedManagers->contains($availableManager));
        $this->assertFalse($releasedManagers->contains($injuredManager));
        $this->assertFalse($releasedManagers->contains($suspendedManager));
        $this->assertFalse($releasedManagers->contains($retiredManager));
    }

    /** @test */
    public function it_can_get_injured_managers()
    {
        $injuredManager = Manager::factory()->injured()->create();
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $injuredManagers = Manager::injured()->get();

        $this->assertCount(1, $injuredManagers);
        $this->assertTrue($injuredManagers->contains($injuredManager));
        $this->assertFalse($injuredManagers->contains($futureEmployedManager));
        $this->assertFalse($injuredManagers->contains($availableManager));
        $this->assertFalse($injuredManagers->contains($suspendedManager));
        $this->assertFalse($injuredManagers->contains($retiredManager));
        $this->assertFalse($injuredManagers->contains($releasedManager));
    }

    /** @test */
    public function it_can_get_suspended_managers()
    {
        $suspendedManager = Manager::factory()->suspended()->create();
        $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $availbleManager = Manager::factory()->available()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $retiredManager = Manager::factory()->retired()->create();
        $releasedManager = Manager::factory()->released()->create();

        $suspendedManagers = Manager::suspended()->get();

        $this->assertCount(1, $suspendedManagers);
        $this->assertTrue($suspendedManagers->contains($suspendedManager));
        $this->assertFalse($suspendedManagers->contains($futureEmployedManager));
        $this->assertFalse($suspendedManagers->contains($availbleManager));
        $this->assertFalse($suspendedManagers->contains($injuredManager));
        $this->assertFalse($suspendedManagers->contains($retiredManager));
        $this->assertFalse($suspendedManagers->contains($releasedManager));
    }

    /** @test */
    public function a_manager_without_an_employment_is_unemployed()
    {
        $manager = Manager::factory()->create();

        $this->assertTrue($manager->isUnemployed());
    }

    /** @test */
    public function a_manager_employed_in_the_future_has_future_employment()
    {
        /** @var \App\Models\Manager $manager */
        $manager = Manager::factory()->create();

        $manager->employ(Carbon::tomorrow());

        $this->assertTrue($manager->hasFutureEmployment());
    }
}
