<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Requests\Titles\RetireRequest;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_retires_an_active_title_and_redirects($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->active()->create();

        $response = $this->retireRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::RETIRED, $title->status);
            $this->assertCount(1, $title->retirements);
            $this->assertEquals($now->toDateTimeString(), $title->retirements->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            RetireController::class,
            '__invoke',
            RetireRequest::class
        );
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $response = $this->retireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_title()
    {
        $title = Title::factory()->create();

        $this->retireRequest($title)->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_retired_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->retired()->create();

        $this->retireRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_a_future_activated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->withFutureActivation()->create();

        $this->retireRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_unactivated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->unactivated()->create();

        $this->retireRequest($title);
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function retiring_an_inactive_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeRetiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->inactive()->create();

        $this->retireRequest($title);
    }
}
