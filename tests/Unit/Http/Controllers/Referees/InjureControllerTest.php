<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Requests\Referees\InjureRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class InjureControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injurable_referee_can_be_injured_with_a_given_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new InjureController;

        $refereeMock->expects()->canBeInjured()->andReturns(true);
        $repositoryMock->expects()->injure($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new InjureRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_referee_that_cannot_be_injured_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new InjureController;

        $refereeMock->expects()->canBeInjured()->andReturns(false);
        $repositoryMock->shouldNotReceive('injured');

        $this->expectException(CannotBeInjuredException::class);

        $controller->__invoke($refereeMock, new InjureRequest, $repositoryMock);
    }
}
