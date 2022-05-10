<?php

namespace Tests\Integration\Http\Livewire\Venues;

use App\Http\Livewire\Venues\VenuesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group venues
 * @group integration-venues
 */
class VenuesListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(VenuesList::class)
            ->assertViewIs('livewire.venues.venues-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(VenuesList::class)
            ->assertViewHas('venues');
    }
}
