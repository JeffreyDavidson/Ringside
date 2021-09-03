<?php

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\ActiveTitles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class ActiveTitlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(ActiveTitles::class)
            ->assertViewIs('livewire.titles.active-titles');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(ActiveTitles::class)
            ->assertViewHas('activeTitles');
    }
}
