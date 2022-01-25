<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use App\Services\RefereeService;
use Tests\TestCase;

/**
 * @group referees
 * @group roster
 * @group services
 */
class RefereeServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_referee_without_an_employment()
    {
        $data = $this->mock(RefereeData::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->create($data)->once();
        $repositoryMock->shouldNotHaveReceived('employ');

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_referee_with_an_employment()
    {
        $data = $this->mock(RefereeData::class);
        $data->start_date = now();
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($refereeMock);
        $repositoryMock->expects()->employ($refereeMock, $data->start_date);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_referee_without_an_employment_start_date()
    {
        $data = $this->mock(RefereeData::class);
        $data->start_date = null;
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->update($refereeMock, $data)->once()->andReturns($refereeMock);
        $refereeMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(false);

        $service->update($refereeMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_referee_and_employ_if_started_at_is_filled()
    {
        $data = $this->mock(RefereeData::class);
        $data->start_date = now();
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->update($refereeMock, $data)->once()->andReturns($refereeMock);
        $refereeMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $refereeMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $repositoryMock->expects()->employ($refereeMock, $data->start_date)->once()->andReturns($refereeMock);

        $service->update($refereeMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_referee()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->delete($refereeMock)->once();

        $service->delete($refereeMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_referee()
    {
        $refereeMock = $this->mock(Referee::class);
        $repositoryMock = $this->mock(RefereeRepository::class);
        $service = new RefereeService($repositoryMock);

        $repositoryMock->expects()->restore($refereeMock)->once();

        $service->restore($refereeMock);
    }
}
