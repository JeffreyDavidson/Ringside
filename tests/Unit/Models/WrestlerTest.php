<?php

namespace Tests\Unit\Models;

use App\Enums\WrestlerStatus;
use App\Models\SingleRosterMember;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group roster
 */
class WrestlerTest extends TestCase
{
    /** @test */
    public function a_wrestler_has_a_name()
    {
        $wrestler = new Wrestler(['name' => 'Example Wrestler Name']);

        $this->assertEquals('Example Wrestler Name', $wrestler->name);
    }

    /** @test */
    public function a_wrestler_has_a_height()
    {
        $wrestler = new Wrestler(['height' => 70]);

        $this->assertEquals('70', $wrestler->height);
    }

    /** @test */
    public function a_wrestler_has_a_weight()
    {
        $wrestler = new Wrestler(['weight' => 210]);

        $this->assertEquals(210, $wrestler->weight);
    }

    /** @test */
    public function a_wrestler_has_a_hometown()
    {
        $wrestler = new Wrestler(['hometown' => 'Los Angeles, California']);

        $this->assertEquals('Los Angeles, California', $wrestler->hometown);
    }

    /** @test */
    public function a_wrestler_can_have_a_signature_move()
    {
        $wrestler = new Wrestler(['signature_move' => 'Example Signature Move']);

        $this->assertEquals('Example Signature Move', $wrestler->signature_move);
    }

    /** @test */
    public function a_wrestler_has_a_status()
    {
        $wrestler = new Wrestler();
        $wrestler->setRawAttributes(['status' => 'example'], true);

        $this->assertEquals('example', $wrestler->getRawOriginal('status'));
    }

    /** @test */
    public function a_wrestler_status_gets_cast_as_a_wrestler_status_enum()
    {
        $wrestler = new Wrestler();

        $this->assertInstanceOf(WrestlerStatus::class, $wrestler->status);
    }

    /** @test */
    public function a_wrestler_uses_has_a_height_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\HasAHeight', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_uses_can_be_stable_member_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeStableMember', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_uses_can_be_tag_team_partner_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeTagTeamPartner', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_uses_can_be_booked_trait()
    {
        $this->assertUsesTrait('App\Models\Concerns\CanBeBooked', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_uses_soft_deleted_trait()
    {
        $this->assertUsesTrait('Illuminate\Database\Eloquent\SoftDeletes', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_has_custom_relationships()
    {
        $this->assertUsesTrait('App\Eloquent\Concerns\HasCustomRelationships', Wrestler::class);
    }

    /** @test */
    public function a_wrestler_is_a_single_roster_member()
    {
        $this->assertEquals(SingleRosterMember::class, get_parent_class(Wrestler::class));
    }
}
