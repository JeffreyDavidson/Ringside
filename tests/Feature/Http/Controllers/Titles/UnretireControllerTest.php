<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use App\Http\Requests\Titles\UnretireRequest;
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
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertNotNull($title->retirements->last()->ended_at);
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(UnretireController::class, '__invoke', UnretireRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::BASIC)
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
     */
    public function invoke_throws_exception_for_unretiring_an_active_title()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->active()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_inactive_title()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->inactive()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_a_future_activated_title()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->withFutureActivation()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_unretiring_an_unactivated_title()
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->unactivated()->create();

        $this->actAs(Role::ADMINISTRATOR)
            ->patch(action([UnretireController::class], $title));
    }
}
