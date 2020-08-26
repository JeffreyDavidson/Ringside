<?php

namespace Tests\Unit\Views\Stables;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\StableFactory;
use Tests\TestCase;

/**
 * @group views
 */
class StableBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_stable_name_can_be_seen_on_their_biography_page()
    {
        $stable = StableFactory::new()->create(['name' => 'Greatest Stable Name']);

        $this->assertView('stables.show', compact('stable'))->contains('Greatest Stable Name');
    }
}
