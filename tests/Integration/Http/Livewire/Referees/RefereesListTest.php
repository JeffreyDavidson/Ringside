<?php

namespace Tests\Integration\Http\Livewire\Referees;

use App\Http\Livewire\Referees\RefereesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group referees
 * @group integration-referees
 */
class RefereesListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(RefereesList::class)
            ->assertViewIs('livewire.referees.referees-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(RefereesList::class)
            ->assertViewHas('referees');
    }
}
