<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class DeactivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_deactivates_an_active_title_and_redirects()
    {
        $title = Title::factory()->active()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) {
            $this->assertNotNull($title->activations->last()->ended_at);
            $this->assertEquals(TitleStatus::INACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(DeactivateController::class, '__invoke', DeactivateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([DeactivateController::class], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->patch(action([DeactivateController::class], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_deactivating_an_unactivated_title()
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->unactivated()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_deactivating_a_future_activated_title()
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->withFutureActivation()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_deactivating_an_inactive_title()
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->inactive()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title));
    }

    /**
     * @test
     */
    public function invoke_throws_exception_for_deactivating_a_retired_title()
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->retired()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title));
    }
}
