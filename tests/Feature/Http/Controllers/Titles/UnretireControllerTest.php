<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_unretires_a_retired_title_and_redirects()
    {
        $title = Title::factory()->retired()->create();

        $this
            ->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertNotNull($title->retirements->last()->ended_at);
            $this->assertEquals(TitleStatus::active(), $title->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::basic())
            ->patch(action([UnretireController::class], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->patch(action([UnretireController::class], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonunretirableTitleTypes
     */
    public function invoke_throws_exception_for_unretiring_a_non_unretirable_title($factoryState)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->{$factoryState}()->create();

        $this->actAs(Role::administrator())
            ->patch(action([UnretireController::class], $title));
    }

    public function nonunretirableTitleTypes()
    {
        return [
            'active title' => ['active'],
            'inactive title' => ['inactive'],
            'with future activation title' => ['withFutureActivation'],
            'unactivated title' => ['unactivated'],
        ];
    }
}
