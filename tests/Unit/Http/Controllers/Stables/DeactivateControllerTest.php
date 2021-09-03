<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Requests\Stables\DeactivateRequest;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Tests\TestCase;

/**
 * @group stables
 * @group controllers
 */
class DeactivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function a_deactivatable_stable_can_be_deactivated_with_a_given_date()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new DeactivateController;

        $stableMock->expects()->canBeDeactivated()->andReturns(true);
        $repositoryMock->expects()->deactivate($stableMock, now()->toDateTimeString())->once()->andReturns($stableMock);
        $repositoryMock->expects()->disassemble($stableMock, now()->toDateTimeString())->once()->andReturns($stableMock);
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $controller->__invoke($stableMock, new DeactivateRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_deactivatable_stable_that_cannot_be_deactivated_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $controller = new DeactivateController;

        $stableMock->expects()->canBeDeactivated()->andReturns(false);
        $repositoryMock->shouldNotReceive('deactivate');

        $this->expectException(CannotBeDeactivatedException::class);

        $controller->__invoke($stableMock, new DeactivateRequest, $repositoryMock);
    }
}
