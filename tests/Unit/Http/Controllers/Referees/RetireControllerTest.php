<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Referees\RetireController;
use App\Http\Requests\Referees\RetireRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_referee_can_be_retired_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new RetireController;

        $refereeMock->expects()->canBeRetired()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(false);
        $refereeMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->release($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_referee_that_is_suspended_needs_to_be_reinstated_before_retire()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new RetireController;

        $refereeMock->expects()->canBeRetired()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(true);
        $refereeMock->expects()->isInjured()->andReturns(false);
        $repositoryMock->expects()->reinstate($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_referee_that_is_injured_needs_to_be_cleared_before_retire()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new RetireController;

        $refereeMock->expects()->canBeRetired()->andReturns(true);
        $refereeMock->expects()->isSuspended()->andReturns(false);
        $refereeMock->expects()->isInjured()->andReturns(true);
        $repositoryMock->expects()->clearInjury($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->release($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_referee_that_cannot_be_retired_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new RetireController;

        $refereeMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($refereeMock, new RetireRequest, $repositoryMock);
    }
}
