<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Referees\UnretireController;
use App\Http\Requests\Referees\UnretireRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_referee_can_be_unretired_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new UnretireController;

        $refereeMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->employ($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_referee_that_cannot_be_unretired_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new UnretireController;

        $refereeMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($refereeMock, new UnretireRequest, $repositoryMock);
    }
}
