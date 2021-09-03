<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;
use App\Models\Wrestler;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group stables
 * @group controllers
 */
class ActivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_activatable_stable_can_be_activated_with_a_given_date()
    {
        $this->markTestIncomplete();
        $stableMock = $this->mock(Stable::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ActivateController;

        $stableMock->expects()->canBeActivated()->andReturns(true);
        $repositoryMock->expects()->activate($stableMock, now()->toDateTimeString())->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->once()->andReturns(collect([$wrestlerMock]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->once()->andReturns(collect([$tagTeamMock]));

        $wrestlerMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $tagTeamMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $tagTeamMock->shouldReceive('getAttribute')->with('currentWrestlers')->once()->andReturns([$wrestlerMock]);
        // $tagTeamRepositoryMock->expects()->employ($tagTeamMock, now()->toDateTimeString())->once()->andReturns($tagTeamMock);
        // $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        // $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($stableMock, new ActivateRequest, $repositoryMock, $wrestlerRepositoryMock, $tagTeamRepositoryMock);
    }

    /**
     * @test
     */
    public function an_activatable_stable_that_cannot_be_activated_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ActivateController;

        $stableMock->expects()->canBeActivated()->andReturns(false);
        $repositoryMock->shouldNotReceive('activate');

        $this->expectException(CannotBeActivatedException::class);

        $controller->__invoke($stableMock, new ActivateRequest, $repositoryMock, $wrestlerRepositoryMock, $tagTeamRepositoryMock);
    }
}
