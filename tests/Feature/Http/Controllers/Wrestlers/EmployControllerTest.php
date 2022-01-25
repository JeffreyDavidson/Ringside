<?php

namespace Tests\Feature\Http\Controllers\Wrestlers;

use App\Enums\Role;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use Tests\TestCase;

/**
 * @group wrestlers
 * @group feature-wrestlers
 * @group roster
 * @group feature-roster
 */
class EmployControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_employs_an_unemployed_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->unemployed()->create();

        $this->assertCount(0, $wrestler->employments);
        $this->assertEquals(WrestlerStatus::unemployed(), $wrestler->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(1, $wrestler->employments);
            $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
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
        $this->assertEquals(WrestlerStatus::future_employment(), $wrestler->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) use ($startedAt) {
            $this->assertTrue($wrestler->currentEmployment->started_at->lt($startedAt));
            $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function invoke_employs_a_released_wrestler_and_redirects()
    {
        $wrestler = Wrestler::factory()->released()->create();

        $this->assertEquals(WrestlerStatus::released(), $wrestler->status);

        $this
            ->actAs(Role::administrator())
            ->patch(action([EmployController::class], $wrestler))
            ->assertRedirect(action([WrestlersController::class, 'index']));

        tap($wrestler->fresh(), function ($wrestler) {
            $this->assertCount(2, $wrestler->employments);
            $this->assertEquals(WrestlerStatus::bookable(), $wrestler->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_employ_a_wrestler()
    {
        $wrestler = Wrestler::factory()->create();

        $this
            ->actAs(Role::basic())
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
     *
     * @dataProvider nonemployableWrestlerTypes
     */
    public function invoke_throws_exception_for_employing_a_non_employable_wrestler($factoryState)
    {
        $this->expectException(CannotBeEmployedException::class);
        $this->withoutExceptionHandling();

        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        $this->actAs(Role::administrator())
            ->patch(action([EmployController::class], $wrestler));
    }

    public function nonemployableWrestlerTypes()
    {
        return [
            'suspended wrestler' => ['suspended'],
            'injured wrestler' => ['injured'],
            'bookable wrestler' => ['bookable'],
            'retired wrestler' => ['retired'],
        ];
    }
}
