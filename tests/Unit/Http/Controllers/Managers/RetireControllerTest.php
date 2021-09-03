<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Managers\RetireController;
use App\Http\Requests\Managers\RetireRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_manager_can_be_retired_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $managerMock->expects()->canBeRetired()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_is_suspended_needs_to_be_reinstated_before_retiring()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->canBeRetired()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(true);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->reinstate($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_is_injured_needs_to_be_cleared_before_retiring()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->canBeRetired()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_has_a_tag_team_can_be_retired()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->canBeRetired()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(true);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);
        $repositoryMock->expects()->removeFromCurrentTagTeams($managerMock)->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_has_a_wrestler_can_be_retired()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->canBeRetired()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(true);
        $repositoryMock->expects()->removeFromCurrentWrestlers($managerMock)->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_cannot_be_retired_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new RetireController;

        $managerMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($managerMock, new RetireRequest, $repositoryMock);
    }
}
