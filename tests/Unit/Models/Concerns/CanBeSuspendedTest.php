<?php

namespace Tests\Unit\Models\Concerns;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use App\Exceptions\CannotBeSuspendedException;
use App\Exceptions\CannotBeReinstatedException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group traits
 */
class CanBeSuspendedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_can_be_suspended($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->states('bookable')->create();

        $model->suspend();

        $this->assertEquals('suspended', $model->status);
        $this->assertCount(1, $model->suspensions);
        $this->assertNull($model->currentSuspension->ended_at);
        $this->assertEquals($now->toDateTimeString(), $model->currentSuspension->started_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_future_employment_single_roster_member_cannot_be_suspended($modelClass)
    {
        $this->expectException(CannotBeSuspendedException::class);

        $model = factory($modelClass)->states('future-employment')->create();

        $model->suspend();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_suspended_single_roster_member_cannot_be_suspended($modelClass)
    {
        $this->expectException(CannotBeSuspendedException::class);

        $model = factory($modelClass)->states('suspended')->create();

        $model->suspend();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_retired_single_roster_member_cannot_be_suspended($modelClass)
    {
        $this->expectException(CannotBeSuspendedException::class);

        $model = factory($modelClass)->states('retired')->create();

        $model->suspend();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function an_suspended_single_roster_member_cannot_be_suspended($modelClass)
    {
        $this->expectException(CannotBeSuspendedException::class);

        $model = factory($modelClass)->states('suspended')->create();

        $model->suspend();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_cannot_be_reinstated($modelClass)
    {
        $this->expectException(CannotBeReinstatedException::class);

        $model = factory($modelClass)->states('bookable')->create();

        $model->reinstate();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_future_employment_single_roster_member_cannot_be_reinstated($modelClass)
    {
        $this->expectException(CannotBeReinstatedException::class);

        $model = factory($modelClass)->states('future-employment')->create();

        $model->reinstate();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function an_injured_single_roster_member_cannot_be_reinstated($modelClass)
    {
        $this->expectException(CannotBeReinstatedException::class);

        $model = factory($modelClass)->states('injured')->create();

        $model->reinstate();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_retired_single_roster_member_cannot_be_reinstated($modelClass)
    {
        $this->expectException(CannotBeReinstatedException::class);

        $model = factory($modelClass)->states('retired')->create();

        $model->reinstate();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_suspended_single_roster_member_can_be_reinstated($modelClass)
    {
        $model = factory($modelClass)->states('suspended')->create();

        $model->reinstate();

        $this->assertEquals('bookable', $model->status);
        $this->assertNotNull($model->previousSuspension->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_with_a_suspension_is_suspended($modelClass)
    {
        $model = factory($modelClass)->states('suspended')->create();

        $this->assertTrue($model->isSuspended());
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function it_can_get_suspended_models($modelClass)
    {
        $suspendedModel = factory($modelClass)->states('suspended')->create();
        $pendingEmploymentModel = factory($modelClass)->states('future-employment')->create();
        $bookableModel = factory($modelClass)->states('bookable')->create();
        $injuredModel = factory($modelClass)->states('injured')->create();
        $retiredModel = factory($modelClass)->states('retired')->create();

        $suspendedModels = $modelClass::suspended()->get();

        $this->assertCount(1, $suspendedModels);
        $this->assertTrue($suspendedModels->contains($suspendedModel));
        $this->assertFalse($suspendedModels->contains($pendingEmploymentModel));
        $this->assertFalse($suspendedModels->contains($bookableModel));
        $this->assertFalse($suspendedModels->contains($injuredModel));
        $this->assertFalse($suspendedModels->contains($retiredModel));;
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_can_be_suspended_multiple_times($modelClass)
    {
        $model = factory($modelClass)->states('suspended')->create();

        $model->reinstate();
        $model->suspend();

        $this->assertCount(1, $model->previousSuspensions);
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Wrestler::class], [Referee::class]];
    }
}
