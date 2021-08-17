<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Requests\Wrestlers\UnretireRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_wrestler_can_be_unretired_with_a_given_date()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new UnretireController;

        $wrestlerMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->employ($wrestlerMock, now()->toDateTimeString())->once()->andReturns();
        $wrestlerMock->expects()->updateStatusAndSave()->once();

        $controller->__invoke($wrestlerMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_wrestler_that_cannot_be_unretired_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new UnretireController;

        $wrestlerMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($wrestlerMock, new UnretireRequest, $repositoryMock);
    }
}
