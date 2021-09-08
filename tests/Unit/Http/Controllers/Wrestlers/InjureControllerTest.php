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
    public function a_wrestler_can_be_injured_with_a_given_date()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new InjureController;

        $wrestlerMock->expects()->canBeInjured()->andReturns(true);
        $repositoryMock->expects()->injure($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->andReturns(null);

        $controller->__invoke($wrestlerMock, new InjureRequest, $repositoryMock);
    }

    /**
     * @test
     */
    public function a_wrestler_can_be_injured_from_a_tag_team()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $controller = new InjureController;

        $wrestlerMock->expects()->canBeInjured()->andReturns(true);
        $repositoryMock->expects()->injure($wrestlerMock, now()->toDateTimeString())->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->getAttribute('currentTagTeam')->times(3)->andReturns($tagTeamMock);
        $tagTeamMock->expects()->exists()->once()->andReturns(true);
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns(true);

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
