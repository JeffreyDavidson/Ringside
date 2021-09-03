<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Managers\UnretireController;
use App\Http\Requests\Managers\UnretireRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_manager_can_be_unretired_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new UnretireController;

        $managerMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->employ($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_manager_that_cannot_be_unretired_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new UnretireController;

        $managerMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($managerMock, new UnretireRequest, $repositoryMock);
    }
}
