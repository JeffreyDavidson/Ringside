<?php

namespace Tests\Integration\Livewire\Titles;

use App\Http\Livewire\Titles\FutureActivationAndUnactivatedTitles;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

class FutureActivationAndUnactivatedTitlesTest extends TestCase
{
    use RefreshDatabase;

    /** @var \Illuminate\Support\Collection */
    protected $titles;

    protected function setUp(): void
    {
        parent::setUp();

        $active = TitleFactory::new()->count(3)->active()->create();
        $futureActivation = TitleFactory::new()->count(3)->futureActivation()->create();
        $unactivated = TitleFactory::new()->count(3)->unactivated()->create();
        $inactive = TitleFactory::new()->count(3)->inactive()->create();
        $retired = TitleFactory::new()->count(3)->retired()->create();

        $this->titles = collect([
            'active'            => $active,
            'future-activation' => $futureActivation,
            'unactivated'       => $unactivated,
            'inactive'          => $inactive,
            'retired'           => $retired,
            'all'               => collect()
                                    ->concat($active)
                                    ->concat($futureActivation)
                                    ->concat($unactivated)
                                    ->concat($inactive)
                                    ->concat($retired),
        ]);
    }

    /** @test */
    public function future_activations_and_unactivated_titles_component_should_return_correct_view()
    {
        $component = Livewire::test(FutureActivationAndUnactivatedTitles::class);

        $this->assertEquals(
            'livewire.titles.future-activation-and-unactivated-titles',
            $component->lastRenderedView->getName()
        );
    }

    /** @test */
    public function future_activations_and_unactivated_titles_component_should_pass_correct_data()
    {
        $component = Livewire::test(FutureActivationAndUnactivatedTitles::class);

        $futureActivationAndUnactivatedTitles = Title::query()
            ->futureActivation()
            ->orWhere
            ->unactivated()
            ->get();

        $component->assertViewHas('futureActivationAndUnactivatedTitles');
        $this->assertCount(6, $futureActivationAndUnactivatedTitles);
        $this->assertEquals(
            $this->titles->only(['future-activation', 'unactivated'])->flatten()->pluck('id')->toArray(),
            $futureActivationAndUnactivatedTitles->pluck('id')->sort()->values()->toArray()
        );
    }
}
