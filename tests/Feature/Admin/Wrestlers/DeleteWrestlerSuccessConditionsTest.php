<?php

namespace Tests\Feature\Admin\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group admins
 */
class DeleteWrestlerSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_pending_introduced_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('pending-introduced')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_retired_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('retired')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_suspended_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('suspended')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }

    /** @test */
    public function an_administrator_can_delete_a_injured_wrestler()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->states('injured')->create();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertRedirect(route('wrestlers.index'));
        $this->assertSoftDeleted('wrestlers', ['name' => $wrestler->name]);
    }
}
