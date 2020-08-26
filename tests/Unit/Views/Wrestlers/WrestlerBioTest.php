<?php

namespace Tests\Unit\Views\Wrestlers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NunoMaduro\LaravelMojito\InteractsWithViews;
use Tests\Factories\WrestlerFactory;
use Tests\TestCase;

/**
 * @group views
 */
class WrestlerBioTest extends TestCase
{
    use RefreshDatabase, InteractsWithViews;

    /** @test */
    public function a_wrestler_name_can_be_seen_on_their_biography_page()
    {
        $wrestler = WrestlerFactory::new()->create(['name' => 'Kid Wonder']);

        $this->assertView('wrestlers.show', compact('wrestler'))->contains('Kid Wonder');
    }

    /** @test */
    public function a_wrestlers_height_can_be_seen_on_their_biography_page()
    {
        $wrestler = WrestlerFactory::new()->create(['height' => 78]);

        $this->assertView('wrestlers.show', compact('wrestler'))->contains('6\'6"');
    }

    /** @test */
    public function a_wrestlers_weight_can_be_seen_on_their_biography_page()
    {
        $wrestler = WrestlerFactory::new()->create(['weight' => 220]);

        $this->assertView('wrestlers.show', compact('wrestler'))->contains('210 lbs.');
    }

    /** @test */
    public function a_wrestlers_hometown_can_be_seen_on_their_biography_page()
    {
        $wrestler = WrestlerFactory::new()->create(['hometown' => 'Los Angeles, CA']);

        $this->assertView('wrestlers.show', compact('wrestler'))->contains('Los Angeles, CA');
    }

    /** @test */
    public function a_wrestlers_signature_move_can_be_seen_on_their_biography_page()
    {
        $wrestler = WrestlerFactory::new()->create(['signature_move' => 'The Finisher']);

        $this->assertView('wrestlers.show', compact('wrestler'))->contains('The Finisher');
    }
}
