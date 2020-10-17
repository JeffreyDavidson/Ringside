<?php

namespace Tests\Unit\Models;

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Exceptions\CannotBeInjuredException;
use App\Exceptions\CannotBeReinstatedException;
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
    public function it_can_get_suspended_models()
    {
        $suspendedmanagers = Manager::factory()->suspended()->create();
        $futureEmploymentManagers = Manager::factory()->withFutureEmployment()->create();
        $availbleManagers = Manager::factory()->available()->create();
        $injuredManagers = Manager::factory()->injured()->create();
        $retiredManagers = Manager::factory()->retired()->create();

        $suspendedModels = Manager::suspended()->get();

        $this->assertCount(1, $suspendedModels);
        $this->assertTrue($suspendedModels->contains($suspendedmanagers));
        $this->assertFalse($suspendedModels->contains($futureEmploymentManagers));
        $this->assertFalse($suspendedModels->contains($availbleManagers));
        $this->assertFalse($suspendedModels->contains($injuredManagers));
        $this->assertFalse($suspendedModels->contains($retiredManagers));
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
    public function it_can_get_injured_models()
    {
        $injuredManager = Manager::factory()->injured()->create();
        $pendingEmploymentManager = Manager::factory()->withFutureEmployment()->create();
        $availableManager = Manager::factory()->available()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();

        $injuredManagers = Manager::injured()->get();

        $this->assertCount(1, $injuredManagers);
        $this->assertTrue($injuredManagers->contains($injuredManager));
        $this->assertFalse($injuredManagers->contains($pendingEmploymentManager));
        $this->assertFalse($injuredManagers->contains($availableManager));
        $this->assertFalse($injuredManagers->contains($suspendedManager));
        $this->assertFalse($injuredManagers->contains($retiredManager));
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
    public function a_bookable_single_roster_member_can_be_fired_default_to_now()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $manager = Manager::factory()->bookable()->create();

        $this->assertNull($manager->currentEmployment->ended_at);

        $manager->fire();

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $manager->previousEmployment->ended_at);
    }

    /** @test */
    public function a_bookable_single_roster_member_can_be_fired_at_start_date()
    {
        $yesterday = Carbon::yesterday();
        Carbon::setTestNow($yesterday);

        $manager = Manager::factory()->bookable()->create();

        $this->assertNull($manager->currentEmployment->ended_at);

        $manager->fire($yesterday);

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

        $manager->fire();

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

        $manager->fire();

        $this->assertCount(1, $manager->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $manager->previousEmployment->ended_at);
        $this->assertNotNull($manager->previousSuspension->ended_at);
    }

    /** @test */
    public function a_future_employment_single_roster_member_cannot_be_fired()
    {
        $this->expectException(CannotBeReleasedException::class);

        $manager = Manager::factory()->withFutureEmployment()->create();

        $manager->fire();
    }

    /** @test */
    public function a_retired_single_roster_member_cannot_be_fired()
    {
        $this->expectException(CannotBeReleasedException::class);

        $manager = Manager::factory()->retired()->create();

        $manager->fire();
    }

    /** @test */
    public function a_single_roster_member_with_an_employment_now_or_in_the_past_is_employed()
    {
        $manager = Manager::factory()->create();
        $manager->currentEmployment()->create(['started_at' => Carbon::now()]);

        $this->assertTrue($manager->checkIsEmployed());
    }

    /** @test */
    public function it_can_get_future_employment_models()
    {
        $pendingEmploymentManager = Manager::factory()->withFutureEmployment()->create();
        $bookableManager = Manager::factory()->bookable()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();

        $pendingEmploymentManagers = Manager::pendingEmployment()->get();

        $this->assertCount(1, $pendingEmploymentManagers);
        $this->assertTrue($pendingEmploymentManagers->contains($pendingEmploymentManager));
        $this->assertFalse($pendingEmploymentManagers->contains($bookableManager));
        $this->assertFalse($pendingEmploymentManagers->contains($injuredManager));
        $this->assertFalse($pendingEmploymentManagers->contains($suspendedManager));
        $this->assertFalse($pendingEmploymentManagers->contains($retiredManager));
    }

    /** @test */
    public function it_can_get_employed_models()
    {
        $pendingEmploymentManager = Manager::factory()->withFutureEmployment()->create();
        $bookableManager = Manager::factory()->bookable()->create();
        $injuredManager = Manager::factory()->injured()->create();
        $suspendedManager = Manager::factory()->suspended()->create();
        $retiredManager = Manager::factory()->retired()->create();

        $employedManagers = Manager::employed()->get();

        $this->assertCount(4, $employedManagers);
        $this->assertFalse($employedManagers->contains($pendingEmploymentManager));
        $this->assertTrue($employedManagers->contains($bookableManager));
        $this->assertTrue($employedManagers->contains($injuredManager));
        $this->assertTrue($employedManagers->contains($suspendedManager));
        $this->assertTrue($employedManagers->contains($retiredManager));
    }

    /** @test */
    public function a_single_roster_member_without_an_employment_is_future_employment()
    {
        $manager = Manager::factory()->create();

        $this->assertTrue($manager->hasFutureEmployment());
    }

    /** @test */
    public function a_single_roster_member_without_a_suspension_or_injury_or_retirement_and_employed_in_the_future_is_future_employment()
    {
        $manager = Manager::factory()->create();
        $manager->employ(Carbon::tomorrow());

        $this->assertTrue($manager->hasFutureEmployment());
    }
}
