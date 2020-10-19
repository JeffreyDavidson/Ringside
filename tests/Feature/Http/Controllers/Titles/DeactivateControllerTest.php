<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Requests\Titles\DeactivateRequest;
use App\Models\Title;
use Carbon\Carbon;
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
     * @dataProvider administrators
     */
    public function invoke_deactivates_an_active_title_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->active()->create();

        $response = $this->deactivateRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::INACTIVE, $title->status);
            $this->assertCount(1, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->ended_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            DeactivateController::class,
            '__invoke',
            DeactivateRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_deactivates_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $this->deactivateRequest($title)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this->deactivateRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_an_unactivated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->unactivated()->create();

        $this->deactivateRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_a_future_activated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->withFutureActivation()->create();

        $this->deactivateRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_an_inactive_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->inactive()->create();

        $this->deactivateRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_a_retired_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->retired()->create();

        $this->deactivateRequest($title);
    }
}
