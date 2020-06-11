<?php

namespace Tests\Integration\Livewire\Titles;

use App\Http\Livewire\Titles\InactiveTitles;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

class InactiveTitlesTest extends TestCase
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
    public function inactive_titles_component_should_return_correct_view()
    {
        $component = Livewire::test(InactiveTitles::class);

        $this->assertEquals(
            'livewire.titles.inactive-titles',
            $component->lastRenderedView->getName()
        );
    }

    /** @test */
    public function inactive_titles_component_should_pass_correct_data()
    {
        $component = Livewire::test(InactiveTitles::class);

        $inactiveTitles = Title::inactive()->get();

        $component->assertViewHas('inactiveTitles');
        $this->assertCount(3, $inactiveTitles);
        $this->assertEquals(
            $this->titles->get('inactive')->pluck('id')->toArray(),
            $inactiveTitles->pluck('id')->sort()->values()->toArray()
        );
    }
}
