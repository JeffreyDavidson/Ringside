<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Requests\Wrestlers\SuspendRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class SuspendControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_suspendable_wrestler_can_be_suspended_with_a_given_date()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new SuspendController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new SuspendRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_wrestler_that_is_on_a_tag_team_can_be_suspended()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new SuspendController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(true);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->save()->once();
        $currentTagTeamRelationMock->expects()->updateStatus()->save()->once();

        $controller->__invoke($wrestlerMock, new SuspendRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_wrestler_that_cannot_be_suspended_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new SuspendController;

        $wrestlerMock->expects()->canBeSuspended()->andReturns(false);
        $repositoryMock->shouldNotReceive('suspend');

        $this->expectException(CannotBeSuspendedException::class);

        $controller->__invoke($wrestlerMock, new SuspendRequest, $repositoryMock);
    }
}
