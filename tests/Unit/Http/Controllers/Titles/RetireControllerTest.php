<?php

namespace Tests\Unit\Http\Controllers\Titles;

use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Tests\TestCase;

/**
 * @group titles
 * @group controllers
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_retirable_title_can_be_retired_with_a_given_date()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new RetireController;

        $titleMock->expects()->canBeRetired()->andReturns(true);
        $repositoryMock->expects()->deactivate($titleMock, now()->toDateTimeString())->once()->andReturns();
        $repositoryMock->expects()->retire($titleMock, now()->toDateTimeString())->once()->andReturns();
        $titleMock->expects()->updateStatus()->once()->andReturns($titleMock);
        $titleMock->expects()->save()->once()->andReturns($titleMock);

        $controller->__invoke($titleMock, new RetireRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_retirable_title_that_cannot_be_retired_throws_an_exception()
    {
        $retirementDate = null;
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new RetireController;

        $titleMock->expects()->canBeRetired()->andReturns(false);
        $repositoryMock->shouldNotReceive('retire');

        $this->expectException(CannotBeRetiredException::class);

        $controller->__invoke($titleMock, new RetireRequest, $repositoryMock);
    }
}
