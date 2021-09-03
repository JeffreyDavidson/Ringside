<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeInjuredException;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Requests\Wrestlers\InjureRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class InjureControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_injured_wrestler_can_be_cleared_from_an_injury_with_a_given_date()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new InjureController;

        $wrestlerMock->expects()->canBeInjured()->andReturns(true);
        $repositoryMock->expects()->injure($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new InjureRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_wrestler_that_cannot_be_injured_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new InjureController;

        $wrestlerMock->expects()->canBeInjured()->andReturns(false);
        $repositoryMock->shouldNotReceive('injure');

        $this->expectException(CannotBeInjuredException::class);

        $controller->__invoke($wrestlerMock, new InjureRequest, $repositoryMock);
    }
}
