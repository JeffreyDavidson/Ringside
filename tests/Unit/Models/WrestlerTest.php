<?php

namespace Tests\Unit\Models;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 * @group models
 */
class WrestlerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_wrestler_has_a_name()
    {
        $wrestler = Wrestler::factory()->create(['name' => 'Example Wrestler Name']);

        $this->assertEquals('Example Wrestler Name', $wrestler->name);
    }

    /** @test */
    public function a_wrestler_has_a_height()
    {
        $wrestler = Wrestler::factory()->create(['height' => 70]);

        $this->assertEquals('70', $wrestler->height);
    }

    /** @test */
    public function a_wrestler_has_a_weight()
    {
        $wrestler = Wrestler::factory()->create(['weight' => 210]);

        $this->assertEquals(210, $wrestler->weight);
    }

    /** @test */
    public function a_wrestler_has_a_hometown()
    {
        $wrestler = Wrestler::factory()->create(['hometown' => 'Los Angeles, California']);

        $this->assertEquals('Los Angeles, California', $wrestler->hometown);
    }

    /** @test */
    public function a_wrestler_can_have_a_signature_move()
    {
        $wrestler = Wrestler::factory()->create(['signature_move' => 'Example Signature Move']);

        $this->assertEquals('Example Signature Move', $wrestler->signature_move);
    }

    /** @test */
    public function a_wrestler_has_a_status()
    {
        $wrestler = Wrestler::factory()->create();
        $wrestler->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $wrestler->getRawOriginal('status'));
    }

    /** @test */
    public function a_wrestler_status_gets_cast_as_a_wrestler_status_enum()
    {
        $wrestler = Wrestler::factory()->create();

        $this->assertInstanceOf(WrestlerStatus::class, $wrestler->status);
    }

    /** @test */
    public function a_wrestler_uses_can_be_stable_member_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeStableMember', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Wrestler::class);
    }

    /**
     * @test
     * @dataProvider unclearableWrestlers
     */
    public function clearing_an_injury_from_an_unemployed_wrestler_throws_an_exception($unclearableWrestlers)
    {
        $this->expectException(CannotBeClearedFromInjuryException::class);
        $this->withoutExceptionHandling();

        $unclearableWrestlers->clearFromInjury();
    }

    /**
     * @test
     * @dataProvider unemployableWrestlers
     */
    public function employing_a_bookable_wrestler_throws_an_exception($unemployableWrestlers)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->bookable()->create();

        $wrestler->employ();
    }

    public function unclearableWrestlers()
    {
        return [
            [Wrestler::factory()->released()->create()],
            [Wrestler::factory()->retired()->create()],
            [Wrestler::factory()->suspended()->create()],
            [Wrestler::factory()->withFutureEmployment()->create()],
            [Wrestler::factory()->bookable()->create()],
            [Wrestler::factory()->unemployed()->create()],
        ];
    }

    public function unemployableWrestlers()
    {
        return [
            [Wrestler::factory()->released()->create()],
            [Wrestler::factory()->retired()->create()],
            [Wrestler::factory()->suspended()->create()],
            [Wrestler::factory()->withFutureEmployment()->create()],
            [Wrestler::factory()->bookable()->create()],
            [Wrestler::factory()->unemployed()->create()],
        ];
    }
}
