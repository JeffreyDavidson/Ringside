<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Http\Controllers\Wrestlers\ClearInjuryController;
use App\Http\Requests\Wrestlers\ClearInjuryRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class ClearInjuryControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injured_wrestler_can_be_cleared_from_an_injury_with_a_date()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ClearInjuryController;

        $wrestlerMock->expects()->canBeClearedFromInjury()->andReturns(true);
        $repositoryMock->expects()->clearInjury($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new ClearInjuryRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_uninjurable_wrestler_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ClearInjuryController;

        $wrestlerMock->expects()->canBeClearedFromInjury()->andReturns(false);
        $repositoryMock->shouldNotReceive('clearInjury');

        $this->expectException(CannotBeClearedFromInjuryException::class);

        $controller->__invoke($wrestlerMock, new ClearInjuryRequest, $repositoryMock);
    }
}
