<?php

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\InactiveTitles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class InactiveTitlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function component_should_return_correct_view()
    {
        Livewire::test(InactiveTitles::class)
            ->assertViewIs('livewire.titles.inactive-titles');
    }

    /**
     * @test
     */
    public function component_should_pass_correct_data()
    {
        Livewire::test(InactiveTitles::class)
                ->assertViewHas('inactiveTitles');
    }
}
