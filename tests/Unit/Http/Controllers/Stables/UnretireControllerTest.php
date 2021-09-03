<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Stables\UnretireController;
use App\Http\Requests\Stables\UnretireRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Tests\TestCase;

/**
 * @group stables
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_stable_can_be_unretired_with_a_given_date()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new UnretireController;

        $stableMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($stableMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->activate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $controller->__invoke($stableMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_stable_that_cannot_be_unretired_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new UnretireController;

        $stableMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($stableMock, new UnretireRequest, $repositoryMock);
    }
}
