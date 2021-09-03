<?php

namespace Tests\Unit\Http\Controllers\Titles;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Tests\TestCase;

/**
 * @group titles
 * @group controllers
 */
class ActivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_activatable_title_can_be_activated_with_a_given_date()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new ActivateController;

        $titleMock->expects()->canBeActivated()->andReturns(true);
        $repositoryMock->expects()->activate($titleMock, now()->toDateTimeString())->once()->andReturns($titleMock);
        $titleMock->expects()->updateStatus()->once()->andReturns($titleMock);
        $titleMock->expects()->save()->once()->andReturns($titleMock);

        $controller->__invoke($titleMock, new ActivateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function an_activatable_title_that_cannot_be_activated_throws_an_exception()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new ActivateController;

        $titleMock->expects()->canBeActivated()->andReturns(false);
        $repositoryMock->shouldNotReceive('activate');

        $this->expectException(CannotBeActivatedException::class);

        $controller->__invoke($titleMock, new ActivateRequest, $repositoryMock);
    }
}
