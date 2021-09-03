<?php

namespace Tests\Integration\Rules;

use App\Models\Wrestler;
use App\Rules\CannotBeHindered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group rules
 */
class CannotBeHinderedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function a_suspended_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = Wrestler::factory()->suspended()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function an_injured_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = Wrestler::factory()->injured()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }

    /**
     * @test
     */
    public function a_retired_wrestler_cannot_join_a_tag_team()
    {
        $wrestler = Wrestler::factory()->retired()->create();

        $this->assertFalse((new CannotBeHindered())->passes(null, $wrestler->id));
    }
}
