<?php

namespace Tests\Unit\Http\Controllers\Managers;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Managers\ReleaseController;
use App\Http\Requests\Managers\ReleaseRequest;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Tests\TestCase;

/**
 * @group managers
 * @group controllers
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_releasable_manager_can_be_released_with_a_given_date()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $managerMock->expects()->canBeReleased()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_manager_that_is_suspended_needs_to_be_reinstated_before_release()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $managerMock->expects()->canBeReleased()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(true);
        $managerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->reinstate($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_manager_that_is_injured_needs_to_be_cleared_before_release()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);

        $managerMock->expects()->canBeReleased()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_manager_that_has_a_tag_team_can_be_released()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->canBeReleased()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(true);
        $managerMock->expects()->has('currentWrestlers')->andReturns(false);
        $repositoryMock->expects()->removeFromCurrentTagTeams($managerMock)->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_manager_that_has_a_wrestler_can_be_released()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->canBeReleased()->andReturns(true);
        $managerMock->expects()->isSuspended()->andReturns(false);
        $managerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($managerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($managerMock, now()->toDateTimeString())->once()->andReturns();
        $managerMock->expects()->updateStatus()->once()->andReturns($managerMock);
        $managerMock->expects()->save()->once()->andReturns($managerMock);

        $managerMock->expects()->has('currentTagTeams')->andReturns(false);
        $managerMock->expects()->has('currentWrestlers')->andReturns(true);
        $repositoryMock->expects()->removeFromCurrentWrestlers($managerMock)->once()->andReturns($managerMock);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_manager_that_cannot_be_released_throws_an_exception()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $controller = new ReleaseController;

        $managerMock->expects()->canBeReleased()->andReturns(false);
        $repositoryMock->shouldNotReceive('release');

        $this->expectException(CannotBeReleasedException::class);

        $controller->__invoke($managerMock, new ReleaseRequest, $repositoryMock);
    }
}
