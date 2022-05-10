<?php

namespace Tests\Integration\Http\Livewire\Stables;

use App\Http\Livewire\Stables\StablesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group stables
 * @group integration-stables
 */
class StablesListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(StablesList::class)
            ->assertViewIs('livewire.stables.stables-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(StablesList::class)
            ->assertViewHas('stables');
    }
}
