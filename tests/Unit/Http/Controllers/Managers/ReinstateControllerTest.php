<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Managers\ReinstateController;
use App\Http\Requests\Managers\ReinstateRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_reinstatable_manager_can_be_reinstated_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReinstateController;

        $managerMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_manager_that_cannot_be_reinstated_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReinstateController;

        $managerMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($managerMock, new ReinstateRequest, $repositoryMock);
    }
}
