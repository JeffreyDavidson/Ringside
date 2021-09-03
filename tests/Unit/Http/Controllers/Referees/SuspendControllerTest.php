<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Referees\SuspendController;
use App\Http\Requests\Referees\SuspendRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class SuspendControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_suspendable_referee_can_be_suspended_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new SuspendController;

        $refereeMock->expects()->canBeSuspended()->andReturns(true);
        $repositoryMock->expects()->suspend($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new SuspendRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_suspendable_referee_that_cannot_be_suspended_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new SuspendController;

        $refereeMock->expects()->canBeSuspended()->andReturns(false);
        $repositoryMock->shouldNotReceive('suspend');

        $this->expectException(CannotBeSuspendedException::class);

        $controller->__invoke($refereeMock, new SuspendRequest, $repositoryMock);
    }
}
