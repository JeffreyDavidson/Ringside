<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Requests\TagTeams\EmployRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group tagteams
 * @group controllers
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_employable_tag_team_can_be_employed_with_a_given_date()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new EmployController;
        $employmentDate = now()->toDateTimeString();

        $tagTeamMock->expects()->canBeEmployed()->andReturns(true);
        $repositoryMock->expects()->employ($tagTeamMock, $employmentDate)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $tagTeamMock->expects()->getAttribute('currentWrestlers')->andReturns([$wrestlerMock]);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $employmentDate)->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($tagTeamMock, new EmployRequest, $repositoryMock, $wrestlerRepositoryMock);
    }

    /**
     * @test
     */
    public function an_employable_tag_team_that_cannot_be_employed_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new EmployController;

        $tagTeamMock->expects()->canBeEmployed()->andReturns(false);
        $repositoryMock->shouldNotReceive('employ');

        $this->expectException(CannotBeEmployedException::class);

        $controller->__invoke($tagTeamMock, new EmployRequest, $repositoryMock, $wrestlerRepositoryMock);
    }
}
