<?php

namespace Tests\Unit\Http\Livewire\Titles;

use App\Http\Livewire\Titles\FutureActivationAndUnactivatedTitles;
use App\Models\Title;
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

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function future_activations_and_unactivated_titles_component_should_return_correct_view()
    {
        $component = Livewire::test(FutureActivationAndUnactivatedTitles::class);

        $this->assertEquals('livewire.titles.future-activation-and-unactivated-titles', $component->lastRenderedView->getName());
    }

    /**
     * @test
     */
    public function future_activations_and_unactivated_titles_component_should_pass_correct_data()
    {
        $futureActivationAndUnactivatedTitles = Title::query()
            ->futureActivation()
            ->orWhere
            ->unactivated()
            ->get();

        Livewire::test(FutureActivationAndUnactivatedTitles::class)->assertSet('futureActivationAndUnactivatedTitles', $futureActivationAndUnactivatedTitles);
    }
}
