<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Requests\Managers\EmployRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_employable_manager_can_be_employed_without_a_date_passed_in()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new EmployController;

        $managerMock->expects()->canBeEmployed()->once()->andReturns(true);
        $repositoryMock->expects()->employ($managerMock, now()->toDateTimeString())->once()->andReturns($managerMock);
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new EmployRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_employable_manager_that_cannot_be_employed_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new EmployController;

        $managerMock->expects()->canBeEmployed()->andReturns(false);
        $repositoryMock->shouldNotReceive('employ');

        $this->expectException(CannotBeEmployedException::class);

        $controller->__invoke($managerMock, new EmployRequest, $repositoryMock);
    }
}
