<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Managers\SuspendController;
use App\Http\Requests\Managers\SuspendRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class SuspendControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_suspendable_manager_can_be_suspended_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new SuspendController;

        $managerMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new SuspendRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_manager_that_cannot_be_suspended_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new SuspendController;

        $managerMock->expects()->canBeSuspended()->andReturns(false);
        $repositoryMock->shouldNotReceive('suspend');

        $this->expectException(CannotBeSuspendedException::class);

        $controller->__invoke($managerMock, new SuspendRequest, $repositoryMock);
    }
}
