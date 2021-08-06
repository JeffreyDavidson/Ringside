<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
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
        $this->withoutExceptionHandling();
        Carbon::setTestNow($now = now());

        $title = Title::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('titles.activate', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) use ($now) {
            $this->assertTrue($title->hasActivations());
            $this->assertTrue($title->isCurrentlyActivated());
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_a_future_activated_title_and_redirects($administrators)
    {
        Carbon::setTestNow($now = now());

        $title = Title::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('titles.activate', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) use ($now) {
            $this->assertTrue($title->hasActivations());
            $this->assertTrue($title->isCurrentlyActivated());
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_activates_an_inactive_title_and_redirects($administrators)
    {
        Carbon::setTestNow($now = now());

        $title = Title::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('titles.activate', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) use ($now) {
            $this->assertTrue($title->isCurrentlyActivated());
            $this->assertCount(2, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(ActivateController::class, '__invoke', ActivateRequest::class);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_activate_a_title()
    {
        $title = Title::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('titles.activate', $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_activate_a_title()
    {
        $title = Title::factory()->create();

        $this->patch(route('titles.activate', $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_an_active_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('titles.activate', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function activating_a_retired_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('titles.activate', $title));
    }
}
