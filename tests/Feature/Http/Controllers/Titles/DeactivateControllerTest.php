<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
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
        $this->withoutExceptionHandling();
        Carbon::setTestNow($now = now());

        $title = Title::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('titles.deactivate', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) use ($now) {
            $this->assertTrue($title->isDeactivated());
            $this->assertCount(1, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->ended_at->toDateTimeString());
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

        $this->actAs(Role::BASIC)
            ->patch(route('titles.deactivate', $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this->patch(route('titles.deactivate', $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_an_unactivated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('titles.deactivate', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_a_future_activated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('titles.deactivate', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_an_inactive_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('titles.deactivate', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function deactivating_a_retired_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('titles.deactivate', $title));
    }
}
