<?php

namespace Tests\Unit\Rules;

use App\Rules\CannotBeHindered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Factories\WrestlerFactory;

class CannotBeHinderedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_suspended_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = WrestlerFactory::new()->suspended()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }

    /** @test */
    public function an_injured_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = WrestlerFactory::new()->injured()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }

    /** @test */
    public function a_retired_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = WrestlerFactory::new()->retired()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }
}
