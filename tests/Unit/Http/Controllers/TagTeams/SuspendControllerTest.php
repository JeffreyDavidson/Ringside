<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class SuspendControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_suspendable_tag_team_can_be_suspended_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new SuspendController;
        $suspensionDate = now()->toDateTimeString();

        $tagTeamMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($tagTeamMock, now()->toDateTimeString())->once()->andReturns();

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->andReturns([$wrestlerMock]);
        $wrestlerRepositoryMock->expects()->suspend($wrestlerMock, $suspensionDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new SuspendRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_tag_team_that_cannot_be_suspended_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new SuspendController;

        $tagTeamMock->expects()->canBeSuspended()->andReturns(false);
        $repositoryMock->shouldNotReceive('suspend');

        $this->expectException(CannotBeSuspendedException::class);

        $controller->__invoke($tagTeamMock, new SuspendRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
