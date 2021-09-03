<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Referees\ReinstateController;
use App\Http\Requests\Referees\ReinstateRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_reinstatable_referee_can_be_reinstated_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReinstateController;

        $refereeMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_referee_that_cannot_be_reinstated_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ReinstateController;

        $refereeMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($refereeMock, new ReinstateRequest, $repositoryMock);
    }
}
