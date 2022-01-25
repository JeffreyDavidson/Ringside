<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\StableData;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
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
        $this->markTestIncomplete();
        $data = $this->mock(StableData::class);
        $wrestlerAMock = $this->createMock(Wrestler::class);
        $wrestlerBMock = $this->createMock(Wrestler::class);
        $tagTeamAMock = $this->createMock(TagTeam::class);
        $tagTeamBMock = $this->createMock(TagTeam::class);
        $data->started_at = now();
        $wrestlerAMock->set('id', 1);
        $wrestlerBMock->shouldReceive('getAttribute')->with('id')->andReturns(2);
        $tagTeamAMock->shouldReceive('getAttribute')->with('id')->andReturns(1);
        $tagTeamBMock->shouldReceive('getAttribute')->with('id')->andReturns(2);
        $data->wrestlers = [$wrestlerAMock->id, $wrestlerBMock->id];
        $data->tag_teams = [$tagTeamAMock->id, $tagTeamBMock->id];
        $stableMock = $this->mock(Stable::class);
        $repositoryMock = $this->mock(StableRepository::class);
        $service = new StableService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($stableMock);
        $repositoryMock->expects()->activate($stableMock, $data->start_date)->once()->andReturns($stableMock);

        $stableMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $stableMock->shouldReceive('getAttribute')->with('currentTagTeams')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers(
            $stableMock,
            $data->wrestlers,
            $data->start_date
        )->once()->andReturns($stableMock);
        $repositoryMock->expects()->addTagTeams(
            $stableMock,
            $data->tag_teams,
            $data->start_date
        )->once()->andReturns($stableMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_stable_without_an_activation()
    {
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
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
        $this->markTestIncomplete();
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
