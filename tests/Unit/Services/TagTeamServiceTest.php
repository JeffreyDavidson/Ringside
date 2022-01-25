<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\TagTeamData;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\TagTeamRepository;
use App\Repositories\WrestlerRepository;
use App\Services\TagTeamService;
use Tests\TestCase;

/**
 * @group tagteams
 * @group roster
 * @group services
 */
class TagTeamServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_tag_team_and_tag_team_partners_with_an_employment()
    {
        $this->markTestIncomplete();
        $data = $this->mock(TagTeamData::class);
        $data->start_date = now();
        $data->wrestlers = collect(['1', '2']);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $wrestlerMock = $this->mock(Wrestler::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($tagTeamMock);
        $repositoryMock->expects()->employ($tagTeamMock, $data['started_at'])->once();
        $wrestlerRepositoryMock->expects()->findById($data->wrestlers[0])->andReturns($wrestlerMock);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $data->start_date)->once();
        $wrestlerRepositoryMock->expects()->findById($data->wrestlers[1])->andReturns($wrestlerMock);
        $wrestlerRepositoryMock->expects()->employ($wrestlerMock, $data->start_date)->once();
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data->wrestlers, $data->start_date)->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_tag_team_and_tag_team_partners_without_an_employment()
    {
        $this->markTestIncomplete();
        $data = $this->mock(TagTeamData::class);
        $data->wrestlers = ['1', '2'];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($tagTeamMock);
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data->wrestlers)->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_tag_team_without_tag_team_partners_and_without_an_employment()
    {
        $data = $this->mock(TagTeamData::class);
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($tagTeamMock);
        $repositoryMock->shouldNotHaveReceived('employ');
        $repositoryMock->shouldNotHaveReceived('addWrestlers');

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_tag_team_and_employ_if_started_at_is_filled()
    {
        $this->markTestIncomplete();
        $data = $this->mock(TagTeamData::class);
        $data->start_date = now();
        $wrestlerA = $this->mock(Wrestler::class);
        $wrestlerB = $this->mock(Wrestler::class);
        $data->wrestlers = [$wrestlerA->getKey(), $wrestlerB->getKey()];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->update($tagTeamMock, $data)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $tagTeamMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $repositoryMock->expects()->employ($tagTeamMock, $data->start_date)->once()->andReturns($tagTeamMock);
        $tagTeamMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data->wrestlers)->once()->andReturns($tagTeamMock);

        $service->update($tagTeamMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_future_employed_tag_team_and_employ_if_started_at_is_filled()
    {
        $this->markTestIncomplete();
        $data = $this->mock(TagTeamData::class);
        $data->start_date = now();
        $wrestlerA = $this->mock(Wrestler::class);
        $wrestlerA->method('__get')->with('id')->willReturn(1);
        $wrestlerB = $this->mock(Wrestler::class);
        $wrestlerB->method('__get')->with('id')->willReturn(2);
        $data->wrestlers = [$wrestlerA->id, $wrestlerB->id];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->update($tagTeamMock, $data)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $tagTeamMock->expects()->isNotInEmployment()->andReturns(false);
        $tagTeamMock->expects()->hasFutureEmployment()->andReturns(true);
        $tagTeamMock->expects()->employedOn($data['started_at'])->andReturns(false);
        $repositoryMock
            ->expects()
            ->updateEmployment($tagTeamMock, $data->start_date)
            ->once()
            ->andReturns($tagTeamMock);
        $tagTeamMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data->wrestlers)->once()->andReturns($tagTeamMock);

        $service->update($tagTeamMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_tag_team()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->delete($tagTeamMock)->once();

        $service->delete($tagTeamMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_tag_team()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $wrestlerRepositoryMock = $this->mock(WrestlerRepository::class);
        $service = new TagTeamService($repositoryMock, $wrestlerRepositoryMock);

        $repositoryMock->expects()->restore($tagTeamMock)->once();

        $service->restore($tagTeamMock);
    }
}
