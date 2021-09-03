<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\EmployRequest;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_employs_an_unemployed_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertCount(0, $wrestler->employments);
        $this->assertEquals(WrestlerStatus::UNEMPLOYED, $wrestler->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_future_employed_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();
        $startedAt = $wrestler->employments->last()->started_at;

        $this->assertTrue(now()->lt($startedAt));
        $this->assertEquals(WrestlerStatus::FUTURE_EMPLOYMENT, $wrestler->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) use ($startedAt) {
            $this->assertTrue($wrestler->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_released_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->released()->create();

        $this->assertEquals(WrestlerStatus::RELEASED, $wrestler->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(2, $wrestler->employments);
            $this->assertEquals(WrestlerStatus::BOOKABLE, $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(EmployController::class, '__invoke', EmployRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([EmployController::class], $wrestler))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_employ_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_employing_an_employed_wrestler()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->employed()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $wrestler));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_employing_a_retired_wrestler()
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->retired()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([EmployController::class], $wrestler));
    }
}
