<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Requests\Titles\ActivateRequest;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class ActivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_unactivated_title_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->unactivated()->create();

        $response = $this->activateRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(1, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_a_future_activated_title_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->withFutureActivation()->create();

        $response = $this->activateRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(1, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_inactive_title_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->inactive()->create();

        $response = $this->activateRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(2, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->last()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            ActivateController::class,
            '__invoke',
            ActivateRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_activate_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->activateRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_activate_a_title()
    {
        $title = Title::factory()->create();

        $this->activateRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_an_active_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->active()->create();

        $this->activateRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_a_retired_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->retired()->create();

        $this->activateRequest($title);
    }
}
