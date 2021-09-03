<?php

namespace Tests\Integration\Http\Livewire\Titles;

use App\Http\Livewire\Titles\FutureActivationAndUnactivatedTitles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group titles
 * @group integration-titles
 */
class FutureActivationAndUnactivatedTitlesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function component_should_return_correct_view()
    {
        Livewire::test(FutureActivationAndUnactivatedTitles::class)
            ->assertViewIs('livewire.titles.future-activation-and-unactivated-titles');
    }

    /**
     * @test
     */
    public function component_should_pass_correct_data()
    {
        Livewire::test(FutureActivationAndUnactivatedTitles::class)
            ->assertViewHas('futureActivationAndUnactivatedTitles');
    }
}
