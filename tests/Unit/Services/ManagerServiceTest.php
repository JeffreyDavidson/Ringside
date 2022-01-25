<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\ManagerData;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use App\Services\ManagerService;
use Tests\TestCase;

/**
 * @group managers
 * @group roster
 * @group services
 */
class ManagerServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_manager_with_an_employment()
    {
        $data = $this->mock(ManagerData::class);
        $data->start_date = now();
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($managerMock);
        $repositoryMock->expects()->employ($managerMock, $data->start_date)->once()->andReturns($managerMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_manager_without_an_employment()
    {
        $data = $this->mock(ManagerData::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->create($data)->once();
        $repositoryMock->shouldNotReceive('employ');

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_manager_without_a_started_at_date()
    {
        $data = $this->mock(ManagerData::class);
        $data->start_date = null;
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->update($managerMock, $data)->once()->andReturns($managerMock);
        $managerMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(false);

        $service->update($managerMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_manager_and_employ_if_started_at_is_filled()
    {
        $data = $this->mock(ManagerData::class);
        $data->start_date = now();
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->update($managerMock, $data)->once()->andReturns($managerMock);
        $managerMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $managerMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $repositoryMock->expects()->employ($managerMock, $data->start_date)->once()->andReturns($managerMock);

        $service->update($managerMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_manager()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->delete($managerMock)->once();

        $service->delete($managerMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_manager()
    {
        $managerMock = $this->mock(Manager::class);
        $repositoryMock = $this->mock(ManagerRepository::class);
        $service = new ManagerService($repositoryMock);

        $repositoryMock->expects()->restore($managerMock)->once();

        $service->restore($managerMock);
    }
}
