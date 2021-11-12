<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\WrestlerRequestDataFactory;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class WrestlerControllerUpdateMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([WrestlersController::class, 'edit'], $wrestler))
            ->assertViewIs('wrestlers.edit')
            ->assertViewHas('wrestler', $wrestler);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(route('wrestlers.edit', $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->get(route('wrestlers.edit', $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function updates_a_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->create([
            'name' => 'Old Wrestler Name',
            'height' => 81,
            'weight' => 300,
            'hometown' => 'Old Location',
            'signature_move' => 'Old Signature Move',
        ]);

        $this
            ->actAs(Role::administrator())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create([
                    'name' => 'New Wrestler Name',
                    'feet' => 6,
                    'inches' => 2,
                    'weight' => 240,
                    'hometown' => 'Laraville, FL',
                    'signature_move' => 'New Signature Move',
                ])
            )
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertEquals('New Wrestler Name', $wrestler->name);
            $this->assertEquals(74, $wrestler->height->inInches());
            $this->assertEquals(240, $wrestler->weight);
            $this->assertEquals('Laraville, FL', $wrestler->hometown);
            $this->assertEquals('New Signature Move', $wrestler->signature_move);
        });
    }

    /**
     * @test
     */
    public function update_can_employ_an_unemployed_wrestler_when_started_at_is_filled()
    {
        $now = now();

        $wrestler = Wrestler::factory()->unemployed()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create([
                    'started_at' => $now->toDateTimeString(),
                ])
            )
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals(
                $now->toDateTimeString('minute'),
                $wrestler->employments->first()->started_at->toDateTimeString('minute')
            );
        });
    }

    /**
     * @test
     */
    public function update_can_employ_a_future_employed_wrestler_when_started_at_is_filled()
    {
        $now = now();
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create([
                    'started_at' => $now->toDateTimeString(),
                ])
            )
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) use ($now) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals(
                $now->toDateTimeString(),
                $wrestler->employments()->first()->started_at->toDateTimeString()
            );
        });
    }

    /**
     * @test
     */
    public function updating_cannot_employ_a_bookable_wrestler_when_started_at_is_filled()
    {
        $wrestler = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create(
                    ['started_at' => $wrestler->employments()->first()->started_at->toDateTimeString()]
                )
            )
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->employments);
        });
    }

    /**
     * @test
     */
    public function updating_cannot_reemploy_a_released_wrestler()
    {
        $wrestler = Wrestler::factory()->released()->create();
        $startDate = $wrestler->employments->last()->started_at->toDateTimeString();

        $this
            ->actAs(Role::administrator())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create([
                    'started_at' => now()->toDateTimeString(),
                ])
            )
            ->assertSessionHasErrors(['started_at']);

        tap($wrestler->fresh(), function ($wrestler) use ($startDate) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertSame($startDate, $wrestler->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this->actAs(Role::basic())
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->from(action([WrestlersController::class, 'edit'], $wrestler))
            ->patch(
                action([WrestlersController::class, 'update'], $wrestler),
                WrestlerRequestDataFactory::new()->withWrestler($wrestler)->create()
            )
            ->assertRedirect(route('login'));
    }
}
