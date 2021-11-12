<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\Stables\UnretireController;
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
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_retired_stable_and_its_members_and_redirects()
    {
        $now = now();
        Carbon::setTestNow($now);

        $stable = Stable::factory()->retired()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::active(), $stable->status);
            $this->assertCount(1, $stable->retirements);
            $this->assertEquals($now->toDateTimeString(), $stable->fresh()->retirements()->latest()->first()->ended_at);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([UnretireController::class], $stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this
            ->patch(action([UnretireController::class], $stable))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonunretirableStableTypes
     */
    public function invoke_throws_exception_for_unretiring_a_non_unretirable_stable($factoryState)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $stable = Stable::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $stable));
    }

    public function nonunretirableStableTypes()
    {
        return [
            'active stable' => ['active'],
            'with future activated stable' => ['withFutureActivation'],
            'inactive stable' => ['inactive'],
            'unactivated stable' => ['unactivated'],
        ];
    }
}
