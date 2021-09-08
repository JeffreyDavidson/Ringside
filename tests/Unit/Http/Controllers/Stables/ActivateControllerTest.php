<?php

namespace Tests\Unit\Http\Controllers\Stables;

use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Requests\Stables\ActivateRequest;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\StableRepository;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use Tests\TestCase;

/**
 * @group stables
 * @group controllers
 */
class ActivateControllerTest extends TestCase
{
    /**
     * @test
     */
    public function an_activatable_stable_can_be_activated_with_a_given_date()
    {
        $stableMock = $this->mock(Stable::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $wrestlerATagTeamPartner = $this->mock(Wrestler::class);
        $wrestlerBTagTeamPartner = $this->mock(Wrestler::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ActivateController;
        $activationDate = now()->toDateTimeString();

        $stableMock->expects()->canBeActivated()->andReturns(true);

        $stableMock->expects()->getAttribute('currentWrestlers')->times(2)->andReturns(collect([$wrestlerMock]));
        $stableMock->expects()->getAttribute('currentTagTeams')->times(2)->andReturns(collect([$tagTeamMock]));
        $tagTeamMock->expects()
            ->getAttribute('currentWrestlers')
            ->once()
            ->andReturns(collect([$wrestlerATagTeamPartner, $wrestlerBTagTeamPartner]));

        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $activationDate)->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->updateStatus()->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->save()->once()->andReturns($wrestlerMock);

        $wrestlerRepositoryMock->expects()->employ(
            $wrestlerATagTeamPartner,
            $activationDate
        )->once()->andReturns($wrestlerATagTeamPartner);
        $wrestlerATagTeamPartner->expects()->updateStatus()->once()->andReturns($wrestlerATagTeamPartner);
        $wrestlerATagTeamPartner->expects()->save()->once()->andReturns($wrestlerATagTeamPartner);

        $wrestlerRepositoryMock->expects()->employ(
            $wrestlerBTagTeamPartner,
            $activationDate
        )->once()->andReturns($wrestlerBTagTeamPartner);
        $wrestlerBTagTeamPartner->expects()->updateStatus()->once()->andReturns($wrestlerBTagTeamPartner);
        $wrestlerBTagTeamPartner->expects()->save()->once()->andReturns($wrestlerBTagTeamPartner);

        $tagTeamRepositoryMock->expects()->employ($tagTeamMock, $activationDate)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->updateStatus()->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->save()->once()->andReturns($tagTeamMock);

        $repositoryMock->expects()->activate($stableMock, $activationDate)->once()->andReturns();
        $stableMock->expects()->updateStatus()->once()->andReturns($stableMock);
        $stableMock->expects()->save()->once()->andReturns($stableMock);

        $controller->__invoke($stableMock, new ActivateRequest, $repositoryMock, $wrestlerRepositoryMock, $tagTeamRepositoryMock);
    }

    /**
     * @test
     */
    public function an_activatable_stable_that_cannot_be_activated_throws_an_exception()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $tagTeamRepositoryMock = $this->mock(TagTeamRepository::class);
        $controller = new ActivateController;

        $stableMock->expects()->canBeActivated()->andReturns(false);
        $repositoryMock->shouldNotReceive('activate');

        $this->expectException(CannotBeActivatedException::class);

        $controller->__invoke($stableMock, new ActivateRequest, $repositoryMock, $wrestlerRepositoryMock, $tagTeamRepositoryMock);
    }
}
