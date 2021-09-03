<?php

namespace Tests\Unit\Http\Controllers\Wrestlers;

use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Requests\Wrestlers\EmployRequest;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group controllers
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_employable_wrestler_can_be_employed_with_a_given_date()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new EmployController;

        $wrestlerMock->expects()->canBeEmployed()->andReturns(true);
        $repositoryMock->expects()->employ($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $controller->__invoke($wrestlerMock, new EmployRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_employable_wrestler_that_cannot_be_employed_throws_an_exception()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new EmployController;

        $wrestlerMock->expects()->canBeEmployed()->andReturns(false);
        $repositoryMock->shouldNotReceive('employ');

        $this->expectException(CannotBeEmployedException::class);

        $controller->__invoke($wrestlerMock, new EmployRequest, $repositoryMock);
    }
}
