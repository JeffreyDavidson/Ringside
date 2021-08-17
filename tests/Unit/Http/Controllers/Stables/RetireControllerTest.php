<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Tests\TestCase;

/**
 * @group stables
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_stable_can_be_retired_with_a_given_date()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(true);
        $repositoryMock->expects()->deactivate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatusAndSave()->once();

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_stable_that_cannot_be_retired_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock);
    }
}