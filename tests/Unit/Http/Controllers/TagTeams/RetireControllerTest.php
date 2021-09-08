<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Requests\TagTeams\RetireRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_tag_team_can_be_retired_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;
        $retirementDate = now()->toDateTimeString();

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->once()->andReturns([$wrestlerMock]);

        $tagTeamMock->expects()->canBeRetired()->andReturns(true);
        $tagTeamMock->expects()->isSuspended()->andReturns(false);

        $wrestlerRepositoryMock->expects()->release($wrestlerMock, $retirementDate);
        $wrestlerRepositoryMock->expects()->retire($wrestlerMock, $retirementDate);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $repositoryMock->expects()->release($tagTeamMock, $retirementDate)->once()->andReturns();
        $repositoryMock->expects()->retire($tagTeamMock, $retirementDate)->once()->andReturns();
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new RetireRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_tag_team_that_is_suspended_must_be_reinstated_first()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;
        $retirementDate = now()->toDateTimeString();

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->twice()->andReturns([$wrestlerMock]);

        $tagTeamMock->expects()->canBeRetired()->andReturns(true);
        $tagTeamMock->expects()->isSuspended()->andReturns(true);

        $repositoryMock->expects()->reinstate($tagTeamMock, $retirementDate)->andReturns($tagTeamMock);
        $wrestlerRepositoryMock->expects()->reinstate($wrestlerMock, $retirementDate)->andReturns($wrestlerMock);

        $wrestlerRepositoryMock->expects()->release($wrestlerMock, $retirementDate);
        $wrestlerRepositoryMock->expects()->retire($wrestlerMock, $retirementDate);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $repositoryMock->expects()->release($tagTeamMock, $retirementDate)->once()->andReturns();
        $repositoryMock->expects()->retire($tagTeamMock, $retirementDate)->once()->andReturns();
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new RetireRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_tag_team_that_cannot_be_retired_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $tagTeamMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($tagTeamMock, new RetireRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
