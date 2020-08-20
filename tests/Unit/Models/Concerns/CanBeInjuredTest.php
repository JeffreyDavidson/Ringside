<?php

namespace Tests\Unit\Models\Concerns;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use App\Exceptions\CannotBeInjuredException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\CannotBeClearedFromInjuryException;

/**
 * @group roster
 * @group traits
 */
class CanBeInjuredTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_can_be_injured($modelClass)
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model = factory($modelClass)->states('bookable')->create();

        $model->injure();

        $this->assertEquals('injured', $model->status);
        $this->assertCount(1, $model->injuries);
        $this->assertNull($model->currentInjury->ended_at);
        $this->assertEquals($now->toDateTimeString(), $model->currentInjury->started_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_future_employment_single_roster_member_cannot_be_injured($modelClass)
    {
        $this->expectException(CannotBeInjuredException::class);

        $model = factory($modelClass)->states('future-employment')->create();

        $model->injure();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_suspended_single_roster_member_cannot_be_injured($modelClass)
    {
        $this->expectException(CannotBeInjuredException::class);

        $model = factory($modelClass)->states('suspended')->create();

        $model->injure();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_retired_single_roster_member_cannot_be_injured($modelClass)
    {
        $this->expectException(CannotBeInjuredException::class);

        $model = factory($modelClass)->states('retired')->create();

        $model->injure();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function an_injured_single_roster_member_cannot_be_injured($modelClass)
    {
        $this->expectException(CannotBeInjuredException::class);

        $model = factory($modelClass)->states('injured')->create();

        $model->injure();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_bookable_single_roster_member_cannot_be_recovered($modelClass)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $model = factory($modelClass)->states('bookable')->create();

        $model->recover();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_future_employment_single_roster_member_cannot_be_recovered($modelClass)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $model = factory($modelClass)->states('future-employment')->create();

        $model->recover();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_suspended_single_roster_member_cannot_be_recovered($modelClass)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $model = factory($modelClass)->states('suspended')->create();

        $model->recover();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_retired_single_roster_member_cannot_be_cleared_from_injury($modelClass)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);

        $model = factory($modelClass)->states('retired')->create();

        $model->recover();
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function an_injured_single_roster_member_can_be_recovered($modelClass)
    {
        $model = factory($modelClass)->states('injured')->create();

        $model->recover();

        $this->assertEquals('bookable', $model->status);
        $this->assertNotNull($model->previousInjury->ended_at);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_with_an_injury_is_injured($modelClass)
    {
        $model = factory($modelClass)->states('injured')->create();

        $this->assertTrue($model->isInjured());
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function it_can_get_injured_models($modelClass)
    {
        $injuredModel = factory($modelClass)->states('injured')->create();
        $pendingEmploymentModel = factory($modelClass)->states('future-employment')->create();
        $bookableModel = factory($modelClass)->states('bookable')->create();
        $suspendedModel = factory($modelClass)->states('suspended')->create();
        $retiredModel = factory($modelClass)->states('retired')->create();

        $injuredModels = $modelClass::injured()->get();

        $this->assertCount(1, $injuredModels);
        $this->assertTrue($injuredModels->contains($injuredModel));
        $this->assertFalse($injuredModels->contains($pendingEmploymentModel));
        $this->assertFalse($injuredModels->contains($bookableModel));
        $this->assertFalse($injuredModels->contains($suspendedModel));
        $this->assertFalse($injuredModels->contains($retiredModel));;
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_can_be_injured_multiple_times($modelClass)
    {
        $model = factory($modelClass)->states('injured')->create();

        $model->recover();
        $model->injure();

        $this->assertCount(1, $model->previousInjuries);
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Wrestler::class], [Referee::class]];
    }
}
