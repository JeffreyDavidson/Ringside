<?php

namespace Tests\Unit\Http\Livewire\Titles;

use App\Http\Livewire\Titles\InactiveTitles;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use TitlesTestTableSeeder;

/**
 * @group titles
 * @group integration-titles
 */
class InactiveTitlesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TitlesTestTableSeeder::class);
    }

    /** @test */
    public function retired_titles_component_should_return_correct_view()
    {
        $component = Livewire::test(RetiredTitles::class);

        $this->assertEquals('livewire.titles.retired-titles', $component->lastRenderedView->getName());
    }

    /** @test */
    public function inactive_titles_component_should_pass_correct_data()
    {
        $component = Livewire::test(InactiveTitles::class);

        $inactiveTitles = Title::inactive()->get();

        $component->assertViewHas('inactiveTitles');
        $this->assertCount(3, $this->titles['inactive']);
        $this->assertEquals($this->titles->only('inactive')->pluck('id')->toArray(), $inactiveTitles->pluck('id')->sort()->values()->toArray());
    }
}
