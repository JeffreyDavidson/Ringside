<?php

namespace Tests\Unit\Services;

use App\Models\Venue;
use App\Repositories\VenueRepository;
use App\Services\VenueService;
use Tests\TestCase;

/**
 * @group venues
 * @group services
 */
class VenueServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_create_a_venue()
    {
        $data = [];
        $venueMock = $this->mock(Venue::class);
        $repositoryMock = $this->mock(VenueRepository::class);
        $service = new VenueService($repositoryMock);

        $repositoryMock->expects()->create($data)->once()->andReturns($venueMock);

        $service->create($data);
    }

    /**
     * @test
     */
    public function it_can_update_a_venue()
    {
        $data = [
            'name' => 'Example Venue',
            'address1' => '123 Main Street',
            'address2' => 'Suite 456',
            'city' => 'Laraville',
            'state' => 'California',
            'zip' => '12345',
        ];
        $venue = Venue::factory()->make(['name' => 'Example Venue', 'address1' => '123 Main Street', 'address2' => 'Suite 123', 'city' => 'Laraville', 'state' => 'California', 'zip' => '12345']);
        $repositoryMock = $this->mock(VenueRepository::class);
        $service = new VenueService($repositoryMock);

        $repositoryMock->expects()->update($venue, $data)->once()->andReturns($venue);

        $service->update($venue, $data);
    }

    /**
     * @test
     */
    public function it_can_delete_a_venue()
    {
        $venue = Venue::factory()->make();
        $repositoryMock = $this->mock(VenueRepository::class);
        $service = new VenueService($repositoryMock);

        $repositoryMock->expects()->delete($venue)->once();

        $service->delete($venue);
    }

    /**
     * @test
     */
    public function it_can_restore_a_venue()
    {
        $venue = new Venue;
        $repositoryMock = $this->mock(VenueRepository::class);
        $service = new VenueService($repositoryMock);

        $repositoryMock->expects()->restore($venue)->once();

        $service->restore($venue);
    }
}
