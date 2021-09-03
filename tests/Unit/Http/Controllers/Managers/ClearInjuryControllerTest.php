<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Requests\Managers\ClearInjuryRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class ClearInjuryControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injured_manager_can_be_cleared_from_an_injury()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ClearInjuryController;

        $managerMock->expects()->canBeClearedFromInjury()->once()->andReturns(true);
        $repositoryMock->expects()->clearInjury($managerMock, now()->toDateTimeString())->once()->andReturns($managerMock);
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ClearInjuryRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_uninjurable_manager_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ClearInjuryController;

        $managerMock->expects()->canBeClearedFromInjury()->andReturns(false);
        $repositoryMock->shouldNotReceive('clearInjury');

        $this->expectException(CannotBeClearedFromInjuryException::class);

        $controller->__invoke($managerMock, new ClearInjuryRequest, $repositoryMock);
    }
}
