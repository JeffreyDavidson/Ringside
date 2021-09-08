<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_reinstatable_wrestler_can_be_reinstated_with_a_given_date()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $wrestlerMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate(
            $wrestlerMock,
            now()->toDateTimeString()
        )->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns(null);

        $controller->__invoke($wrestlerMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_wrestler_on_a_tag_team_can_be_reinstated()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $wrestlerMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate(
            $wrestlerMock,
            now()->toDateTimeString()
        )->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->times(3)->andReturns($tagTeamMock);
        $tagTeamMock->expects()->exists()->once()->andReturns(true);
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns(true);

        $controller->__invoke($wrestlerMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_wrestler_that_cannot_be_reinstated_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $wrestlerMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($wrestlerMock, new ReinstateRequest, $repositoryMock);
    }
}
