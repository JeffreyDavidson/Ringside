<?php

namespace Tests\Unit\Http\Controllers\Titles;

use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Tests\TestCase;

/**
 * @group titles
 * @group controllers
 */
class DeactivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_deactivatable_title_can_be_deactivated_with_a_given_date()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new DeactivateController;

        $titleMock->expects()->canBeDeactivated()->andReturns(true);
        $repositoryMock->expects()->deactivate($titleMock, now()->toDateTimeString())->once()->andReturns();
        $titleMock->expects()->updateStatus()->once()->andReturns($titleMock);
        $titleMock->expects()->save()->once()->andReturns($titleMock);

        $controller->__invoke($titleMock, new DeactivateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_deactivatable_title_that_cannot_be_deactivated_throws_an_exception()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $controller = new DeactivateController;

        $titleMock->expects()->canBeDeactivated()->andReturns(false);
        $repositoryMock->shouldNotReceive('deactivate');

        $this->expectException(CannotBeDeactivatedException::class);

        $controller->__invoke($titleMock, new DeactivateRequest, $repositoryMock);
    }
}
