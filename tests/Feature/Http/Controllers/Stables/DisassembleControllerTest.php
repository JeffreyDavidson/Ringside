<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Exceptions\CannotBeDisassembledException;
use App\Http\Controllers\Stables\DisassembleController;
use App\Http\Requests\Stables\DisassembleRequest;
use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class DisassembleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_disassembles_an_active_stable_and_its_members_and_redirectsP($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $stable = Stable::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disassemble', $stable))
            ->assertRedirect(route('stables.index'));

        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::INACTIVE, $stable->status);
            $this->assertEquals($now->toDateTimeString(), $stable->activations->last()->ended_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(DisassembleController::class, '__invoke', DisassembleRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_disassemble_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('stables.disassemble', $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_disassemble_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->patch(route('stables.disassemble', $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disassembling_an_inactive_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisassembledException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disassemble', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disassembling_an_retired_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisassembledException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disassemble', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disassembling_an_unactivated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisassembledException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disassemble', $stable));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function disassembling_a_future_activated_stable_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDisassembledException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('stables.disassemble', $stable));
    }
}
