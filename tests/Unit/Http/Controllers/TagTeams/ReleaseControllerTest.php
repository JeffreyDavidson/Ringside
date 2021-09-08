<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\TagTeams\ReleaseController;
use App\Http\Requests\TagTeams\ReleaseRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_releasable_tag_team_can_be_released_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;
        $releaseDate = now()->toDateTimeString();

        $tagTeamMock->expects()->isSuspended()->once()->andReturns(false);

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->andReturns([$wrestlerMock]);
        $wrestlerRepositoryMock->expects()->release($wrestlerMock, $releaseDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $tagTeamMock->expects()->canBeReleased()->andReturns(true);
        $repositoryMock->expects()->release($tagTeamMock, $releaseDate)->once()->andReturns();
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new ReleaseRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_tag_team_that_is_suspended_must_be_reinstated_first()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;
        $releaseDate = now()->toDateTimeString();

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->twice()->andReturns([$wrestlerMock]);

        $tagTeamMock->expects()->isSuspended()->once()->andReturns(true);
        $repositoryMock->expects()->reinstate($tagTeamMock, $releaseDate)->andReturns($tagTeamMock);
        $wrestlerRepositoryMock->expects()->reinstate($wrestlerMock, $releaseDate);

        $wrestlerRepositoryMock->expects()->release($wrestlerMock, $releaseDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $tagTeamMock->expects()->canBeReleased()->andReturns(true);
        $repositoryMock->expects()->release($tagTeamMock, $releaseDate)->once()->andReturns();
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new ReleaseRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_tag_team_that_cannot_be_released_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $tagTeamMock->expects()->canBeReleased()->andReturns(false);
        $repositoryMock->shouldNotReceive('release');

        $this->expectException(CannotBeReleasedException::class);

        $controller->__invoke($tagTeamMock, new ReleaseRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
