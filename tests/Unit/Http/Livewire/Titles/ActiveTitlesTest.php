<?php

namespace Tests\Unit\Http\Livewire\Titles;

use App\Http\Livewire\Titles\ActiveTitles;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use TitlesTestTableSeeder;

/**
 * @group titles
 * @group integration-titles
 */
class ActiveTitlesTest extends TestCase
{
    use RefreshDatabase;

    private $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TitlesTestTableSeeder::class);

        $this->component = Livewire::test(ActiveTitles::class);
    }

    /**
     * @test
     */
    public function it_should_return_correct_view()
    {
        $this->assertEquals('livewire.titles.active-titles', $this->component->lastRenderedView->getName());
    }

    /**
     * @test
     */
    public function it_should_pass_correct_data()
    {
        $activeTitles = Title::active()->get();

        $this->component->assertViewHas('activeTitles');
        $this->assertCount(3, $activeTitles);
    }
}
