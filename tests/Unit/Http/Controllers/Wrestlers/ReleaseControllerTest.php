<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Requests\Wrestlers\ReleaseRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_releasable_wrestler_can_be_released_with_a_given_date()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeReleased()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_wrestler_that_is_suspended_needs_to_be_reinstated_before_release()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeReleased()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(true);
        $wrestlerMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->reinstate($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatus()->save()->once();

        $controller->__invoke($wrestlerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_wrestler_that_is_injured_needs_to_be_cleared_before_release()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(false);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeReleased()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatus()->save()->once();

        $controller->__invoke($wrestlerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_wrestler_that_has_a_tag_team_can_be_released()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $tagTeamMock = $this->mock(TagTeam::class);
        $currentTagTeamRelationMock = $this->mock(Relation::class);
        $currentTagTeamRelationMock->expects()->exists()->andReturns(true);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns($currentTagTeamRelationMock);

        $wrestlerMock->expects()->canBeReleased()->andReturns(true);
        $wrestlerMock->expects()->isSuspended()->andReturns(false);
        $wrestlerMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatus()->save()->once();
        $wrestlerMock->expects()->removeFromCurrentTagTeam();

        $controller->__invoke($wrestlerMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_wrestler_that_cannot_be_released_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReleaseController;

        $wrestlerMock->expects()->canBeReleased()->andReturns(false);
        $repositoryMock->shouldNotReceive('release');

        $this->expectException(CannotBeReleasedException::class);

        $controller->__invoke($wrestlerMock, new ReleaseRequest, $repositoryMock);
    }
}
