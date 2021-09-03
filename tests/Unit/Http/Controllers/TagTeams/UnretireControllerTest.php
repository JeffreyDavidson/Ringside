<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Http\Requests\TagTeams\UnretireRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
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
        $this->markTestIncomplete();
        $unretireDate = now()->toDateTimeString();
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new UnretireController;

        $tagTeamMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($tagTeamMock, $unretireDate)->once()->andReturns();

        $controller->__invoke($tagTeamMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_tag_team_that_cannot_be_unretired_throws_an_exception()
    {
        $this->markTestIncomplete();
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new UnretireController;

        $tagTeamMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($tagTeamMock, new UnretireRequest, $repositoryMock);
    }
}
