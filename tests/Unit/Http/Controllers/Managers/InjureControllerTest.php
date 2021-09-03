<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Requests\Managers\InjureRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class InjureControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injurable_manager_can_be_injured_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new InjureController;

        $managerMock->expects()->canBeInjured()->andReturns(true);
        $repositoryMock->expects()->injure($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new InjureRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_manager_that_cannot_be_injured_throws_an_exception()
    {
        $injureDate = null;
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new InjureController;

        $managerMock->expects()->canBeInjured()->andReturns(false);
        $repositoryMock->shouldNotReceive('injure');

        $this->expectException(CannotBeInjuredException::class);

        $controller->__invoke($managerMock, new InjureRequest, $repositoryMock);
    }
}
