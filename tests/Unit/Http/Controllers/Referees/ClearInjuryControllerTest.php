<?php

namespace Tests\Unit\Http\Controllers\Referees;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Requests\Referees\ClearInjuryRequest;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Tests\TestCase;

/**
 * @group referees
 * @group controllers
 */
class ClearInjuryControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injured_referee_can_be_cleared_from_an_injury_with_a_date()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ClearInjuryController;

        $refereeMock->expects()->canBeClearedFromInjury()->andReturns(true);
        $repositoryMock->expects()->clearInjury($refereeMock, now()->toDateTimeString())->once()->andReturns();
        $refereeMock->expects()->updateStatus()->once()->andReturns($refereeMock);
        $refereeMock->expects()->save()->once()->andReturns($refereeMock);

        $controller->__invoke($refereeMock, new ClearInjuryRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_uninjurable_referee_throws_an_exception()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $controller = new ClearInjuryController;

        $refereeMock->expects()->canBeClearedFromInjury()->andReturns(false);
        $repositoryMock->shouldNotReceive('clearInjury');

        $this->expectException(CannotBeClearedFromInjuryException::class);

        $controller->__invoke($refereeMock, new ClearInjuryRequest, $repositoryMock);
    }
}
