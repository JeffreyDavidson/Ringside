<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_tag_team_can_be_unretired_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new UnretireController;
        $unretireDate = now()->toDateTimeString();

        $tagTeamMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($tagTeamMock, $unretireDate)->once()->andReturns();

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->andReturns([$wrestlerMock]);
        $wrestlerRepositoryMock->expects()->unretire($wrestlerMock, $unretireDate)->andReturns($wrestlerMock);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $unretireDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $repositoryMock->expects()->employ($tagTeamMock, $unretireDate)->andReturns($tagTeamMock);
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $controller->__invoke($tagTeamMock, new UnretireRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_tag_team_that_cannot_be_unretired_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new UnretireController;

        $tagTeamMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($tagTeamMock, new UnretireRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
