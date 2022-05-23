<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use Tests\Factories\RefereeRequestDataFactory;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class RefereeControllerUpdateMethodTest extends TestCase
{
    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->get(action([RefereesController::class, 'edit'], $referee))
            ->assertViewIs('referees.edit')
            ->assertViewHas('referee', $referee);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(ROLE::BASIC)
            ->get(action([RefereesController::class, 'edit'], $referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->get(action([RefereesController::class, 'edit'], $referee))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_a_referee()
    {
        $referee = Referee::factory()->create(['first_name' => 'John', 'last_name' => 'Smith']);

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()->withReferee($referee)->create([
                    'first_name' => 'James',
                    'last_name' => 'Williams',
                ])
            )
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) {
            $this->assertEquals('James', $referee->first_name);
            $this->assertEquals('Williams', $referee->last_name);
        });
    }

    /**
     * @test
     */
    public function update_can_employ_an_unemployed_referee_when_started_at_is_filled()
    {
        $now = now()->toDateTimeString();
        $referee = Referee::factory()->unemployed()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()->withReferee($referee)->create(['started_at' => $now])
            )
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now, $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function update_can_employ_a_future_employed_referee_when_started_at_is_filled()
    {
        $now = now()->toDateTimeString();
        $referee = Referee::factory()->withFutureEmployment()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()->withReferee($referee)->create(['started_at' => $now])
            )
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap($referee->fresh(), function ($referee) use ($now) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($now, $referee->employments()->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function update_cannot_reemploy_a_released_referee()
    {
        $referee = Referee::factory()->released()->create();
        $startDate = $referee->startedAt->toDateTimeString();

        $this->assertCount(1, $referee->employments);

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => now()->toDateTimeString(),
                    ])
            )
            ->assertSessionHasErrors(['started_at']);

        tap($referee->fresh(), function ($referee) use ($startDate) {
            $this->assertSame($startDate, $referee->employments->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function updating_cannot_employ_a_bookable_referee_when_started_at_is_filled()
    {
        $referee = Referee::factory()->bookable()->create();

        $this
            ->actAs(ROLE::ADMINISTRATOR)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()
                    ->withReferee($referee)
                    ->create([
                        'started_at' => now()->toDateTImeString(),
                    ])
            )
            ->assertSessionHasErrors(['started_at']);

        tap($referee->fresh(), function ($referee) {
            $this->assertCount(1, $referee->employments);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->actAs(ROLE::BASIC)
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_referee()
    {
        $referee = Referee::factory()->create();

        $this
            ->from(action([RefereesController::class, 'edit'], $referee))
            ->put(
                action([RefereesController::class, 'update'], $referee),
                RefereeRequestDataFactory::new()->withReferee($referee)->create()
            )
            ->assertRedirect(route('login'));
    }
}
