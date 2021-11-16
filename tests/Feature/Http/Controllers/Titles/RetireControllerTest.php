<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class RetireControllerTest extends TestCase
{
    /**
     * @test
     */
    public function invoke_retires_an_active_title_and_redirects()
    {
        $title = Title::factory()->active()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertCount(1, $title->retirements);
            $this->assertEquals(TitleStatus::retired(), $title->status);
        });
    }

    /**
     * @test
     */
    public function invoke_retires_an_inactive_title_and_redirects()
    {
        $title = Title::factory()->inactive()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertCount(1, $title->retirements);
            $this->assertEquals(TitleStatus::retired(), $title->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_retire_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([RetireController::class], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_retire_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->patch(action([RetireController::class], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonretirableTitleTypes
     */
    public function invoke_throws_exception_for_retiring_a_non_retirable_title($factoryState)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([RetireController::class], $title));
    }

    public function nonretirableTitleTypes()
    {
        return [
            'retired title' => ['retired'],
            'with future activation title' => ['withFutureActivation'],
            'unactivated title' => ['unactivated'],
        ];
    }
}
