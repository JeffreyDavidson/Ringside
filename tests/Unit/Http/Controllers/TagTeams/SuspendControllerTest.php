<?php

namespace Tests\Unit\Http\Controllers\TagTeams;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Requests\TagTeams\SuspendRequest;
use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
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
        $this->markTestIncomplete();
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new SuspendController;

        $tagTeamMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($tagTeamMock, now()->toDateTimeString())->once()->andReturns();

        $controller->__invoke($tagTeamMock, new SuspendRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_tag_team_that_cannot_be_suspended_throws_an_exception()
    {
        $this->markTestIncomplete();
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new SuspendController;

        $tagTeamMock->expects()->canBeSuspended()->andReturns(false);
        $repositoryMock->shouldNotReceive('suspend');

        $this->expectException(CannotBeSuspendedException::class);

        $controller->__invoke($tagTeamMock, new SuspendRequest, $repositoryMock);
    }
}
