<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Referees\ReleaseController;
use App\Http\Requests\Referees\ReleaseRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class ReleaseControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_releasable_referee_can_be_released_with_a_given_date()
    {
        $releaseDate = now()->toDateTimeString();
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReleaseController;

        $refereeMock->expects()->canBeReleased()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(false);
        $refereeMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($refereeMock, $releaseDate)->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_referee_that_is_suspended_needs_to_be_reinstated_before_release()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReleaseController;

        $refereeMock->expects()->canBeReleased()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(true);
        $refereeMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->reinstate($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_referee_that_is_injured_needs_to_be_cleared_before_release()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReleaseController;

        $refereeMock->expects()->canBeReleased()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(false);
        $refereeMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new ReleaseRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_releasable_referee_that_cannot_be_released_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReleaseController;

        $refereeMock->expects()->canBeReleased()->andReturns(false);
        $repositoryMock->shouldNotReceive('release');

        $this->expectException(CannotBeReleasedException::class);

        $controller->__invoke($refereeMock, new ReleaseRequest, $repositoryMock);
    }
}
