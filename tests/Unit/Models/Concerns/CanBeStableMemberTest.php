<?php

namespace Tests\Unit\Models\Concerns;

use Tests\TestCase;
use App\Models\Stable;
use App\Models\Manager;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group traits
 */
class CanBeStableMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_model_has_a_current_stable_after_joining($modelClass)
    {
        $model = factory($modelClass)->states('bookable')->create();
        $stable = StableFactory::new()->states('active')->create();

        $model->stableHistory()->attach($stable);

        $this->assertEquals($stable->id, $model->currentStable->id);
        $this->assertTrue($model->stableHistory->contains($stable));
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_stable_remains_in_a_models_history_after_leaving($modelClass)
    {
        $model = factory($modelClass)->create();
        $stable = StableFactory::new()->create();
        $model->stableHistory()->attach($stable);
        $model->stableHistory()->detach($stable);

        $this->assertTrue($model->previousStables->contains($stable));
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Wrestler::class]];
    }
}
