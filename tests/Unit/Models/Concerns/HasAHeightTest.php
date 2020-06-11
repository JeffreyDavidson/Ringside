<?php

namespace Tests\Unit\Models\Concerns;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group roster
 * @group traits
 */
class HasAHeightTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function some_models_has_a_formatted_height($modelClass)
    {
        $model = factory($modelClass)->create(['height' => 71]);

        $this->assertEquals('5\'11"', $model->formatted_height);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function some_models_can_get_height_in_feet($modelClass)
    {
        $model = factory($modelClass)->create(['height' => 71]);

        $this->assertEquals(5, $model->feet);
    }

    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function some_models_can_get_height_in_inches($modelClass)
    {
        $model = factory($modelClass)->create(['height' => 71]);

        $this->assertEquals(11, $model->inches);
    }

    public function modelClassDataProvider()
    {
        return [[Wrestler::class]];
    }
}
