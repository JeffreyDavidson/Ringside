<?php

namespace Tests\Unit\Http\Livewire\Titles;

use App\Http\Livewire\Titles\RetiredTitles;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use TitlesTestTableSeeder;

/**
 * @group titles
 * @group integration-titles
 */
class RetiredTitlesTest extends TestCase
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
    public function retired_titles_component_should_pass_correct_data()
    {
        $component = Livewire::test(RetiredTitles::class);

        $retiredTitles = Title::retired()->get();

        $component->assertViewHas('retiredTitles');
        $this->assertCount(3, $retiredTitles);
        $this->assertEquals($this->titles->get('retired')->pluck('id')->toArray(), $retiredTitles->pluck('id')->sort()->values()->toArray());
    }
}
