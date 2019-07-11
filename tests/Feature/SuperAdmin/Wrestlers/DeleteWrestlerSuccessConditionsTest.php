<?php

namespace Tests\Feature\SuperAdmin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group suoeradmins
 */
class DeleteWrestlerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_delete_a_bookable_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('bookable')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_an_inactive_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('inactive')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_retired_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_suspended_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function a_super_administrator_can_delete_a_injured_wrestler()
    {
        $this->actAs('super-administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }
}
