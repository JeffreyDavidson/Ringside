<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Requests\Wrestlers\ReinstateRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class ReinstateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_reinstatable_wrestler_can_be_reinstated_with_a_given_date()
    {
        $this->markTestIncomplete();
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $wrestlerMock->expects()->canBeReinstated()->andReturns(true);
        $repositoryMock->expects()->reinstate($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new ReinstateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_reinstatable_wrestler_that_cannot_be_reinstated_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new ReinstateController;

        $wrestlerMock->expects()->canBeReinstated()->andReturns(false);
        $repositoryMock->shouldNotReceive('reinstate');

        $this->expectException(CannotBeReinstatedException::class);

        $controller->__invoke($wrestlerMock, new ReinstateRequest, $repositoryMock);
    }
}
