<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Requests\TagTeams\ReinstateRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
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
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ReinstateController;

        $tagTeamMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate($tagTeamMock, now()->toDateTimeString())->once()->andReturns();
        $tagTeamMock->expects()->updateStatusAndSave()->once();

        $controller->__invoke($tagTeamMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_tag_team_that_cannot_be_reinstated_throws_an_exception()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ReinstateController;

        $tagTeamMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($tagTeamMock, new ReinstateRequest, $repositoryMock);
    }
}