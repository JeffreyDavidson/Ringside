<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\TitleChampionshipsList;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class TitleChampionshipsListTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        Livewire::test(TitleChampionshipsList::class)
            ->assertViewIs('livewire.titles.title-championships-list');
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        Livewire::test(TitleChampionshipsList::class)
            ->assertViewHas('titleChampionships');
    }
}
