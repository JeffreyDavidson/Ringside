<?php

namespace Tests\Unit\Services;

use App\Models\TagTeam;
use App\Repositories\TagTeamRepository;
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
        $data = ['started_at' => now()->toDateTimeString(), 'wrestlers' => ['1', '2']];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($tagTeamMock);
        $repositoryMock->expects()->employ($tagTeamMock, $data['started_at'])->once();
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data['wrestlers'], $data['started_at'])->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_tag_team_and_tag_team_partners_without_an_employment()
    {
        $data = ['wrestlers' => ['1', '2']];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($tagTeamMock);
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data['wrestlers'])->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_tag_team_without_tag_team_partners_and_without_an_employment()
    {
        $data = [];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

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
        $data = ['started_at' => now()->toDateTimeString(), 'wrestlers' => [1, 2]];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

        $repositoryMock->expects()->update($tagTeamMock, $data)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $tagTeamMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $repositoryMock->expects()->employ($tagTeamMock, $data['started_at'])->once()->andReturns($tagTeamMock);
        $tagTeamMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data['wrestlers'])->once()->andReturns($tagTeamMock);

        $service->update($tagTeamMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_future_employed_tag_team_and_employ_if_started_at_is_filled()
    {
        $data = ['started_at' => now()->toDateTimeString(), 'wrestlers' => [1, 2]];
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

        $repositoryMock->expects()->update($tagTeamMock, $data)->once()->andReturns($tagTeamMock);
        $tagTeamMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $tagTeamMock->expects()->isNotInEmployment()->andReturns(false);
        $tagTeamMock->expects()->hasFutureEmployment()->andReturns(true);
        $tagTeamMock->expects()->employedOn($data['started_at'])->andReturns(false);
        $repositoryMock->expects()->updateEmployment($tagTeamMock, $data['started_at'])->once()->andReturns($tagTeamMock);
        $tagTeamMock->shouldReceive('getAttribute')->with('currentWrestlers')->andReturns(collect([]));
        $repositoryMock->expects()->addWrestlers($tagTeamMock, $data['wrestlers'])->once()->andReturns($tagTeamMock);

        $service->update($tagTeamMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_tag_team()
    {
        $tagTeamMock = $this->mock(TagTeam::class);
        $repositoryMock = $this->mock(TagTeamRepository::class);
        $service = new TagTeamService($repositoryMock);

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
        $service = new TagTeamService($repositoryMock);

        $repositoryMock->expects()->restore($tagTeamMock)->once();

        $service->restore($tagTeamMock);
    }
}
