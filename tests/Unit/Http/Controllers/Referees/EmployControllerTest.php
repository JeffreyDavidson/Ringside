<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Requests\Referees\EmployRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_employable_referee_can_be_employed_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new EmployController;

        $refereeMock->expects()->canBeEmployed()->andReturns(true);
        $repositoryMock->expects()->employ($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new EmployRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_employable_referee_that_cannot_be_employed_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new EmployController;

        $refereeMock->expects()->canBeEmployed()->andReturns(false);
        $repositoryMock->shouldNotReceive('employ');

        $this->expectException(CannotBeEmployedException::class);

        $controller->__invoke($refereeMock, new EmployRequest, $repositoryMock);
    }
}
