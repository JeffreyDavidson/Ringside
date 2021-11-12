<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_retired_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->retired()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertNotNull($wrestler->retirements->last()->ended_at);
            $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([UnretireController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([UnretireController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonunretirableWrestlerTypes
     */
    public function invoke_throws_exception_for_unretiring_a_non_unretirable_wrestler($factoryState)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $wrestler));
    }

    public function nonunretirableWrestlerTypes()
    {
        return [
            'bookable wrestler' => ['bookable'],
            'with future employed wrestler' => ['withFutureEmployment'],
            'injured wrestler' => ['injured'],
            'released wrestler' => ['released'],
            'suspended wrestler' => ['suspended'],
            'unemployed wrestler' => ['unemployed'],
        ];
    }
}
