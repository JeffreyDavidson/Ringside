<?php

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\RetiredTitles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class RetiredTitlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function component_should_return_correct_view()
    {
        Livewire::test(RetiredTitles::class)
            ->assertViewIs('livewire.titles.retired-titles');
    }

    /**
     * @test
     */
    public function component_should_pass_correct_data()
    {
        Livewire::test(RetiredTitles::class)
            ->assertViewHas('retiredTitles');
    }
}
