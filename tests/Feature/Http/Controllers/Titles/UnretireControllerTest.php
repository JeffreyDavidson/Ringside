<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\UnretireController;
use App\Http\Requests\Titles\UnretireRequest;
use App\Models\Title;
use Carbon\Carbon;
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
     * @dataProvider administrators
     */
    public function invoke_unretires_a_retired_title_and_redirects($administrators)
    {
        $this->withoutExceptionHandling();
        Carbon::setTestNow($now = now());

        $title = Title::factory()->retired()->create();

        $this->actAs($administrators)
            ->patch(route('titles.unretire', $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) use ($now) {
            $this->assertTrue($title->isCurrentlyActivated());
            $this->assertEquals($now->toDateTimeString(), $title->fresh()->retirements()->latest()->first()->ended_at);
        });
    }

    /** @test */
    public function invoke_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(UnretireController::class, '__invoke', UnretireRequest::class);
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this->actAs(Role::BASIC)
            ->patch(route('titles.unretire', $title))
            ->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this->patch(route('titles.unretire', $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_active_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->active()->create();

        $this->actAs($administrators)
            ->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_inactive_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->inactive()->create();

        $this->actAs($administrators)
            ->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_activated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->withFutureActivation()->create();

        $this->actAs($administrators)
            ->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unactivated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->unactivated()->create();

        $this->actAs($administrators)
            ->patch(route('titles.unretire', $title));
    }
}
