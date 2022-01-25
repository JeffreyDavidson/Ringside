<?php

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
class RefereeControllerStoreMethodTest extends TestCase
{
    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([RefereesController::class, 'create']))
            ->assertViewIs('referees.create')
            ->assertViewHas('referee', new Referee);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_referee()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([RefereesController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_creating_a_referee()
    {
        $this
            ->get(action([RefereesController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_referee_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([RefereesController::class, 'create']))
            ->post(action([RefereesController::class, 'store'], RefereeRequestDataFactory::new()->create([
                'first_name' => 'James',
                'last_name' => 'Williams',
                'started_at' => null,
            ])))
            ->assertRedirect(action([RefereesController::class, 'index']));

        tap(Referee::first(), function ($referee) {
            $this->assertEquals('James', $referee->first_name);
            $this->assertEquals('Williams', $referee->last_name);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_not_created_for_the_referee_if_started_at_is_filled_in_request()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([RefereesController::class, 'create']))
            ->post(
                action([RefereesController::class, 'index']),
                RefereeRequestDataFactory::new()->create(['started_at' => null])
            );

        tap(Referee::first(), function ($referee) {
            $this->assertCount(0, $referee->employments);
        });
    }

    /**
     * @test
     */
    public function an_employment_is_created_for_the_referee_if_started_at_is_filled_in_request()
    {
        $this->withoutExceptionHandling();
        $startedAt = now()->toDateTimeString();

        $this
            ->actAs(Role::administrator())
            ->from(action([RefereesController::class, 'create']))
            ->post(
                action([RefereesController::class, 'store']),
                RefereeRequestDataFactory::new()->create(['started_at' => $startedAt])
            );

        tap(Referee::first(), function ($referee) use ($startedAt) {
            $this->assertCount(1, $referee->employments);
            $this->assertEquals($startedAt, $referee->employments->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_referee()
    {
        $this
            ->actAs(Role::basic())
            ->from(action([RefereesController::class, 'create']))
            ->post(action([RefereesController::class, 'store']), RefereeRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_referee()
    {
        $this
            ->from(action([RefereesController::class, 'create']))
            ->post(action([RefereesController::class, 'store']), RefereeRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
