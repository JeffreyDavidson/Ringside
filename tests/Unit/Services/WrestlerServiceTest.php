<?php

namespace Tests\Unit\Services;

use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use App\Services\WrestlerService;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group services
 */
class WrestlerServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_wrestler_with_an_employment()
    {
        $data = ['started_at' => now()->toDateTimeString()];

        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($wrestlerMock);
        $repositoryMock->expects()->employ($wrestlerMock, $data['started_at'])->once()->andReturns($wrestlerMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_wrestler_without_an_employment()
    {
        $data = [];

        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->create($data)->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_wrestler_without_an_employment_start_date()
    {
        $data = [];
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->update($wrestlerMock, $data)->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(false);

        $service->update($wrestlerMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_wrestler_and_employ_if_started_at_is_filled()
    {
        $data = ['started_at' => now()->toDateTimeString()];
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->update($wrestlerMock, $data)->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $wrestlerMock->expects()->isNotInEmployment()->once()->andReturns(true);
        $repositoryMock->expects()->employ($wrestlerMock, $data['started_at'])->once()->andReturns($wrestlerMock);

        $service->update($wrestlerMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_wrestler_employment_date_when_wrestler_has_future_employment()
    {
        $data = ['started_at' => now()->toDateTimeString()];
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->update($wrestlerMock, $data)->once()->andReturns($wrestlerMock);
        $wrestlerMock->expects()->canHaveEmploymentStartDateChanged()->once()->andReturns(true);
        $wrestlerMock->expects()->isNotInEmployment()->once()->andReturns(false);
        $wrestlerMock->expects()->hasFutureEmployment()->once()->andReturns(true);
        $wrestlerMock->expects()->employedOn($data['started_at'])->andReturns(false);
        $repositoryMock->expects()->updateEmployment($wrestlerMock, $data['started_at'])->once()->andReturns($wrestlerMock);

        $service->update($wrestlerMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_wrestler()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->delete($wrestlerMock)->once();

        $service->delete($wrestlerMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_wrestler()
    {
        $wrestlerMock = $this->mock(Wrestler::class);
        $repositoryMock = $this->mock(WrestlerRepository::class);
        $service = new WrestlerService($repositoryMock);

        $repositoryMock->expects()->restore($wrestlerMock)->once();

        $service->restore($wrestlerMock);
    }
}
