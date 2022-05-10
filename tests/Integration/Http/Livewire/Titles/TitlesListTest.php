<?php

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\TitlesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class TitlesListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(TitlesList::class)
            ->assertViewIs('livewire.titles.titles-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(TitlesList::class)
            ->assertViewHas('titles');
    }
}
