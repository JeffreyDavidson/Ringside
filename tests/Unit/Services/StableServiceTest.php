<?php

namespace Tests\Unit\Services;

use App\Models\Stable;
use App\Repositories\StableRepository;
use App\Services\StableService;
use Tests\TestCase;

/**
 * @group stables
 * @group roster
 * @group services
 */
class StableServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_stable_with_an_activation()
    {
        $data = ['started_at' => now()->toDateTimeString(), 'wrestlers' => [1, 2], 'tag_teams' => [1, 2]];
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($stableMock);
        $repositoryMock->expects()->activate($stableMock, $data['started_at'])->once()->andReturns($stableMock);

        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers(
            $stableMock,
            $data['wrestlers'],
            $data['started_at']
        )->once()->andReturns($stableMock);
        $repositoryMock->expects()->addTagTeams(
            $stableMock,
            $data['tag_teams'],
            $data['started_at']
        )->once()->andReturns($stableMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_stable_without_an_activation()
    {
        $data = ['wrestlers' => [1, 2], 'tag_teams' => [1, 2]];
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);
        $activationDate = now()->toDateTimeString();

        $repositoryMock->expects()->create($data)->once()->andReturns($stableMock);
        $repositoryMock->shouldNotReceive('employ');
        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers(
            $stableMock,
            $data['wrestlers'],
            $activationDate
        )->once()->andReturns($stableMock);
        $repositoryMock->expects()->addTagTeams(
            $stableMock,
            $data['tag_teams'],
            $activationDate
        )->once()->andReturns($stableMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_stable_without_an_activation_start_date()
    {
        $data = ['wrestlers' => [1, 2], 'tag_teams' => [1, 2]];
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);
        $activationDate = now()->toDateTimeString();

        $repositoryMock->expects()->update($stableMock, $data)->once()->andReturns($stableMock);
        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers(
            $stableMock,
            $data['wrestlers'],
            $activationDate
        )->once()->andReturns($stableMock);
        $repositoryMock->expects()->addTagTeams(
            $stableMock,
            $data['tag_teams'],
            $activationDate
        )->once()->andReturns($stableMock);

        $service->update($stableMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_stable_and_activate_if_started_at_is_filled()
    {
        $data = ['wrestlers' => [1, 2], 'tag_teams' => [1, 2]];
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);
        $activationDate = now()->toDateTimeString();

        $repositoryMock->expects()->update($stableMock, $data)->once()->andReturns($stableMock);
        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers(
            $stableMock,
            $data['wrestlers'],
            $activationDate
        )->once()->andReturns($stableMock);
        $repositoryMock->expects()->addTagTeams(
            $stableMock,
            $data['tag_teams'],
            $activationDate
        )->once()->andReturns($stableMock);

        $service->update($stableMock, $data);
    }

    /**
     * @test
     */
    public function it_can_activate_a_stable_that_is_not_in_activation()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);
        $activationDate = now()->toDateTimeString();

        $stableMock->expects()->isNotInActivation()->once()->andReturns(true);
        $repositoryMock->expects()->activate($stableMock, $activationDate)->once()->andReturns($stableMock);

        $service->activateOrUpdateActivation($stableMock, now()->toDateTimeString());
    }

    /**
     * @test
     */
    public function it_can_update_a_stable_activation_date_when_stable_has_future_activation()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);
        $activationDate = now()->toDateTimeString();

        $stableMock->expects()->isNotInActivation()->once()->andReturns(false);
        $stableMock->expects()->hasFutureActivation()->once()->andReturns(true);
        $stableMock->expects()->activatedOn(now()->toDateTimeString())->once()->andReturns(false);
        $repositoryMock->expects()->updateActivation($stableMock, $activationDate)->once()->andReturns($stableMock);

        $service->activateOrUpdateActivation($stableMock, now()->toDateTimeString());
    }

    /**
     * @test
     */
    public function it_can_delete_a_stable()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);

        $repositoryMock->expects()->delete($stableMock)->once();

        $service->delete($stableMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_stable()
    {
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);

        $repositoryMock->expects()->restore($stableMock)->once();

        $service->restore($stableMock);
    }
}
