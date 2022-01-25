<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\TitleData;
use App\Models\Title;
use App\Repositories\TitleRepository;
use App\Services\TitleService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * @group titles
 * @group services
 */
class TitleServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_title_without_an_activation()
    {
        $data = $this->mock(TitleData::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->create($data)->once();

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_create_a_title_with_an_activation()
    {
        $data = $this->mock(TitleData::class);
        $data->activation_date = Carbon::now();
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($titleMock);
        $repositoryMock->expects()->activate($titleMock, $data->activation_date)->once()->andReturns($titleMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_title_without_an_activation_start_date()
    {
        $data = $this->mock(TitleData::class);
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->update($titleMock, $data)->once()->andReturns($titleMock);
        $titleMock->expects()->canHaveActivationStartDateChanged()->once()->andReturns(false);

        $service->update($titleMock, $data);
    }

    /**
     * @test
     */
    public function it_can_update_a_title_and_activate_if_started_at_is_filled()
    {
        $data = $this->mock(TitleData::class);
        $data->activation_date = now();
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->update($titleMock, $data)->once()->andReturns($titleMock);
        $titleMock->expects()->canHaveActivationStartDateChanged()->once()->andReturns(true);
        $titleMock->expects()->isUnactivated()->once()->andReturns(true);
        $repositoryMock->expects()->activate($titleMock, $data->activation_date);

        $service->update($titleMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_title()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->delete($titleMock)->once();

        $service->delete($titleMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_title()
    {
        $titleMock = $this->mock(Title::class);
        $repositoryMock = $this->mock(TitleRepository::class);
        $service = new TitleService($repositoryMock);

        $repositoryMock->expects()->restore($titleMock)->once();

        $service->restore($titleMock);
    }
}
