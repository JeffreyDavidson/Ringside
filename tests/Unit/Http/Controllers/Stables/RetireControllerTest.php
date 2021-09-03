<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Requests\Stables\RetireRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
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
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(true);
        $repositoryMock->expects()->deactivate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $stableMock->expects()->has('currentTagTeams')->andReturns(false);
        $stableMock->expects()->has('currentWrestlers')->andReturns(false);

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock, $tagTeamRepositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_stable_retires_its_current_tag_teams()
    {
        $stableMock = $this->mock(Stable::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(true);
        $repositoryMock->expects()->deactivate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $stableMock->expects()->has('currentTagTeams')->andReturns(true);
        $stableMock->expects()->has('currentWrestlers')->andReturns(false);
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->once()->andReturn(collect([$tagTeamMock]));
        $tagTeamRepositoryMock->expects()->release($tagTeamMock, now()->toDateTimeString());
        $tagTeamRepositoryMock->expects()->retire($tagTeamMock, now()->toDateTimeString());
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock, $tagTeamRepositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_stable_retires_its_current_wrestlers()
    {
        $stableMock = $this->mock(Stable::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(true);
        $repositoryMock->expects()->deactivate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $stableMock->expects()->has('currentTagTeams')->andReturns(false);
        $stableMock->expects()->has('currentWrestlers')->andReturns(true);
        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->once()->andReturn(collect([$wrestlerMock]));
        $wrestlerRepositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString());
        $wrestlerRepositoryMock->expects()->retire($wrestlerMock, now()->toDateTimeString());
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock, $tagTeamRepositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_stable_that_cannot_be_retired_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $stableMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($stableMock, new RetireRequest, $repositoryMock, $tagTeamRepositoryMock, $wrestlerRepositoryMock);
    }
}
