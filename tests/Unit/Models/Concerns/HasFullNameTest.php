<?php

namespace Tests\Unit\Models\Concerns;

use Tests\TestCase;
use App\Models\Manager;
use App\Models\Referee;

/**
 * @group roster
 * @group traits
 */
class HasFullNameTest extends TestCase
{
    /**
     * @test
     * @dataProvider modelClassDataProvider
     */
    public function some_models_has_a_full_name($modelClass)
    {
        $model = factory($modelClass)->make(['first_name' => 'John', 'last_name' => 'Smith']);

        $this->assertEquals('John Smith', $model->full_name);
    }

    public function modelClassDataProvider()
    {
        return [[Manager::class], [Referee::class]];
    }
}
