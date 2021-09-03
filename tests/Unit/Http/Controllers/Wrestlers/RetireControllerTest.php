<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Requests\Wrestlers\RetireRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_wrestler_can_be_retired_with_a_given_date()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeRetired()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $repositoryMock->expects()->retire($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_wrestler_that_is_suspended_needs_to_be_reinstated_before_retiring()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeRetired()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $repositoryMock->expects()->retire($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_manager_that_is_injured_needs_to_be_cleared_before_retiring()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeRetired()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatus()->save()->once();

        $controller->__invoke($wrestlerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_wrestler_that_is_on_a_tag_team_can_be_retired()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $tagTeamMock = $this->mock(TagTeam::class);
        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(true);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeRetired()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatus()->save()->once();
        $wrestlerMock->expects()->removeFromCurrentTagTeam();

        $controller->__invoke($wrestlerMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_wrestler_that_cannot_be_retired_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new RetireController;

        $wrestlerMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($wrestlerMock, new RetireRequest, $repositoryMock);
    }
}
