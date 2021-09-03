<?php

namespace Tests\Unit\Http\Controllers\Titles;

use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\UnretireController;
use App\Http\Requests\Titles\UnretireRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Tests\TestCase;

/**
 * @group titles
 * @group controllers
 */
class UnretireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_unretirable_title_can_be_unretired_with_a_given_date()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new UnretireController;

        $titleMock->expects()->canBeUnretired()->andReturns(true);
        $repositoryMock->expects()->unretire($titleMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->activate($titleMock, now()->toDateTimeString())->once()->andReturns();
        $titleMock->expects()->updateStatus()->once()->andReturns($titleMock);
        $titleMock->expects()->save()->once()->andReturns($titleMock);

        $controller->__invoke($titleMock, new UnretireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_unretirable_title_that_cannot_be_unretired_throws_an_exception()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new UnretireController;

        $titleMock->expects()->canBeUnretired()->andReturns(false);
        $repositoryMock->shouldNotReceive('unretire');

        $this->expectException(CannotBeUnretiredException::class);

        $controller->__invoke($titleMock, new UnretireRequest, $repositoryMock);
    }
}
