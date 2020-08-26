<?php

namespace Tests\Unit\Views\Referees;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\RefereeFactory;
use Tests\TestCase;

/**
 * @group views
 */
class RefereeBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_referee_name_can_be_seen_on_their_biography_page()
    {
        $referee = RefereeFactory::new()->create(['first_name' => 'John', 'last_name' => 'Smith']);

        $this->assertView('referees.show', compact('referee'))->contains('John Smith');
    }
}
