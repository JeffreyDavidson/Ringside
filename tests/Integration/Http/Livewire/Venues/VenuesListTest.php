<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Livewire\Venues;

use App\Http\Livewire\Venues\VenuesList;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group venues
 * @group integration-venues
 */
class VenuesListTest extends TestCase
{
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
