<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_reinstatable_tag_team_can_be_reinstated_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;
        $reinstatementDate = now()->toDateTimeString();

        $tagTeamMock->expects()->canBeReinstated()->andReturns(true);

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->andReturns([$wrestlerMock]);
        $wrestlerRepositoryMock->expects()->reinstate($wrestlerMock, $reinstatementDate)->andReturns($wrestlerMock);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $reinstatementDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $repositoryMock->expects()->reinstate($tagTeamMock, $reinstatementDate)->once()->andReturns();
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new ReinstateRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_tag_team_that_cannot_be_reinstated_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $tagTeamMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($tagTeamMock, new ReinstateRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
