<?php

namespace Tests\Integration\Http\Livewire\Wrestlers;

use App\Http\Livewire\Wrestlers\WrestlersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group integration-wrestlers
 */
class WrestlersListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(WrestlersList::class)
            ->assertViewIs('livewire.wrestlers.wrestlers-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(WrestlersList::class)
            ->assertViewHas('wrestlers');
    }
}
