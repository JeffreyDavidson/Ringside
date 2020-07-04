<?php

namespace Tests\Integration\Livewire\Titles;

use App\Http\Livewire\Titles\InactiveTitles;
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
    public function render_returns_a_view()
    {
        $component = Livewire::test(InactiveTitles::class);

        $this->assertEquals(
            'livewire.titles.inactive-titles',
            $component->lastRenderedView->getName()
        );

        $component->assertViewHas('inactiveTitles');

        $this->assertCount(3, $this->titles['inactive']);
        $this->assertEquals(
            $this->titles->only('inactive')->pluck('id')->toArray(),
            $inactiveTitles->pluck('id')->sort()->values()->toArray()
        );
    }
}
