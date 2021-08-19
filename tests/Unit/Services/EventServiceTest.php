<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Repositories\EventRepository;
use App\Services\EventService;
use Tests\TestCase;

/**
 * @group events
 * @group services
 */
class EventServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_event()
    {
        $data = ['name' => 'Example Event', 'date' => now()->toDateTimeString(), 'venue_id' => 1, 'preview' => 'Example preview to be seen.'];

        $eventMock = $this->mock(Event::class);
        $repositoryMock = $this->mock(EventRepository::class);
        $service = new EventService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($eventMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_event()
    {
        $data = ['name' => 'Example Event', 'date' => now()->toDateTimeString(), 'venue_id' => 1, 'preview' => 'Example preview to be seen.'];

        $eventMock = $this->mock(Event::class);
        $repositoryMock = $this->mock(EventRepository::class);
        $service = new EventService($repositoryMock);

        $repositoryMock->expects()->update($eventMock, $data)->once()->andReturns($eventMock);

        $service->update($eventMock, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_event()
    {
        $eventMock = $this->mock(Event::class);
        $repositoryMock = $this->mock(EventRepository::class);
        $service = new EventService($repositoryMock);

        $repositoryMock->expects()->delete($eventMock)->once();

        $service->delete($eventMock);
    }

    /**
     * @test
     */
    public function it_can_restore_a_event()
    {
        $eventMock = $this->mock(Event::class);
        $repositoryMock = $this->mock(EventRepository::class);
        $service = new EventService($repositoryMock);

        $repositoryMock->expects()->restore($eventMock)->once();

        $service->restore($eventMock);
    }
}
