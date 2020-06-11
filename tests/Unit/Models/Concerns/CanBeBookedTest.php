<?php

namespace Tests\Unit\Models\Concerns;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group roster
 * @group traits
 */
class CanBeBookedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function it_can_get_bookable_models($modelClass)
    {
        $bookableModel = factory($modelClass)->states('bookable')->create();
        $pendingEmploymentModel = factory($modelClass)->states('pending-employment')->create();
        $injuredModel = factory($modelClass)->states('injured')->create();
        $suspendedModel = factory($modelClass)->states('suspended')->create();
        $retiredModel = factory($modelClass)->states('retired')->create();

        $bookableModels = $modelClass::bookable()->get();

        $this->assertCount(1, $bookableModels);
        $this->assertTrue($bookableModels->contains($bookableModel));
        $this->assertFalse($bookableModels->contains($pendingEmploymentModel));
        $this->assertFalse($bookableModels->contains($injuredModel));
        $this->assertFalse($bookableModels->contains($suspendedModel));
        $this->assertFalse($bookableModels->contains($retiredModel));;
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function a_single_roster_member_without_a_suspension_or_injury_or_retirement_and_employed_in_the_past_is_bookable($modelClass)
    {
        $model = factory($modelClass)->states('bookable')->create();
        $model->employments()->create(['started_at' => Carbon::yesterday()]);

        $this->assertTrue($model->checkIsBookable());
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Wrestler::class], [Referee::class]];
    }
}
