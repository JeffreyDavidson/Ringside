<?php

namespace Tests\Unit\Models\Concerns;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use App\Exceptions\CannotBeFiredException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group roster
 * @group traits
 */
class CanBeEmployedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_can_be_employed_default_to_now($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->create();

        $model->employ();

        $this->assertCount(1, $model->employments);
        $this->assertEquals($now->toDateTimeString(), $model->currentEmployment->started_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_can_be_employed_at_start_date($modelClass)
    {
        $yesterday = Carbon::yesterday();
        Carbon::setTestNow($yesterday);

        $model = factory($modelClass)->create();

        $model->employ($yesterday);

        $this->assertEquals($yesterday->toDateTimeString(), $model->currentEmployment->started_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_with_an_employment_in_the_future_can_be_employed_at_start_date($modelClass)
    {
        $today = Carbon::today();
        Carbon::setTestNow($today);

        $model = factory($modelClass)->create();
        $model->employments()->create(['started_at' => Carbon::tomorrow()]);

        $model->employ($today);

        $this->assertEquals($today->toDateTimeString(), $model->currentEmployment->started_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_can_be_fired_default_to_now($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->states('bookable')->create();

        $this->assertNull($model->currentEmployment->ended_at);

        $model->fire();

        $this->assertCount(1, $model->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $model->previousEmployment->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_can_be_fired_at_start_date($modelClass)
    {
        $yesterday = Carbon::yesterday();
        Carbon::setTestNow($yesterday);

        $model = factory($modelClass)->states('bookable')->create();

        $this->assertNull($model->currentEmployment->ended_at);

        $model->fire($yesterday);

        $this->assertCount(1, $model->previousEmployments);
        $this->assertEquals($yesterday->toDateTimeString(), $model->previousEmployment->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function an_injured_single_roster_member_can_be_fired_default_to_now($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->states('injured')->create();

        $this->assertNull($model->currentInjury->ended_at);

        $model->fire();

        $this->assertCount(1, $model->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $model->previousEmployment->ended_at);
        $this->assertNotNull($model->previousInjury->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_suspended_single_roster_member_can_be_fired_default_to_now($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->states('suspended')->create();

        $this->assertNull($model->currentSuspension->ended_at);

        $model->fire();

        $this->assertCount(1, $model->previousEmployments);
        $this->assertEquals($now->toDateTimeString(), $model->previousEmployment->ended_at);
        $this->assertNotNull($model->previousSuspension->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_pending_employment_single_roster_member_cannot_be_fired($modelClass)
    {
        $this->expectException(CannotBeFiredException::class);

        $model = factory($modelClass)->states('pending-employment')->create();

        $model->fire();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_retired_single_roster_member_cannot_be_fired($modelClass)
    {
        $this->expectException(CannotBeFiredException::class);

        $model = factory($modelClass)->states('retired')->create();

        $model->fire();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_with_an_employment_now_or_in_the_past_is_employed($modelClass)
    {
        $model = factory($modelClass)->create();
        $model->currentEmployment()->create(['started_at' => Carbon::now()]);

        $this->assertTrue($model->checkIsEmployed());
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function it_can_get_pending_employment_models($modelClass)
    {
        $pendingEmploymentModel = factory($modelClass)->states('pending-employment')->create();
        $bookableModel = factory($modelClass)->states('bookable')->create();
        $injuredModel = factory($modelClass)->states('injured')->create();
        $suspendedModel = factory($modelClass)->states('suspended')->create();
        $retiredModel = factory($modelClass)->states('retired')->create();

        $pendingEmploymentModels = $modelClass::pendingEmployment()->get();

        $this->assertCount(1, $pendingEmploymentModels);
        $this->assertTrue($pendingEmploymentModels->contains($pendingEmploymentModel));
        $this->assertFalse($pendingEmploymentModels->contains($bookableModel));
        $this->assertFalse($pendingEmploymentModels->contains($injuredModel));
        $this->assertFalse($pendingEmploymentModels->contains($suspendedModel));
        $this->assertFalse($pendingEmploymentModels->contains($retiredModel));
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function it_can_get_employed_models($modelClass)
    {
        $pendingEmploymentModel = factory($modelClass)->states('pending-employment')->create();
        $bookableModel = factory($modelClass)->states('bookable')->create();
        $injuredModel = factory($modelClass)->states('injured')->create();
        $suspendedModel = factory($modelClass)->states('suspended')->create();
        $retiredModel = factory($modelClass)->states('retired')->create();

        $employedModels = $modelClass::employed()->get();

        $this->assertCount(4, $employedModels);
        $this->assertFalse($employedModels->contains($pendingEmploymentModel));
        $this->assertTrue($employedModels->contains($bookableModel));
        $this->assertTrue($employedModels->contains($injuredModel));
        $this->assertTrue($employedModels->contains($suspendedModel));
        $this->assertTrue($employedModels->contains($retiredModel));
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_without_an_employment_is_pending_employment($modelClass)
    {
        $model = factory($modelClass)->create();

        $this->assertTrue($model->isPendingEmployment());
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_without_a_suspension_or_injury_or_retirement_and_employed_in_the_future_is_pending_employment($modelClass)
    {
        $model = factory($modelClass)->create();
        $model->employ(Carbon::tomorrow());

        $this->assertTrue($model->isPendingEmployment());
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Wrestler::class], [Referee::class]];
    }
}
