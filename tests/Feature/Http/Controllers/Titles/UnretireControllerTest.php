<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
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
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = Title::factory()->retired()->create();

        $response = $this->patch(route('titles.unretire', $title));

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(1, $title->retirements);
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
        $this->actAs(Role::BASIC);
        $title = Title::factory()->create();

        $response = $this->patch(route('titles.unretire', $title));

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_title()
    {
        $title = Title::factory()->create();

        $this->patch(route('titles.unretire', $title))->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_active_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->active()->create();

        $this->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_inactive_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->inactive()->create();

        $this->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_a_future_activated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->withFutureActivation()->create();

        $this->patch(route('titles.unretire', $title));
    }

    /**
     * @test
     * @dataProvider administrators
     */
    public function unretiring_an_unactivated_title_throws_an_exception($administrators)
    {
        $this->expectException(CannotBeUnretiredException::class);
        $this->withoutExceptionHandling();

        $this->actAs($administrators);

        $title = Title::factory()->unactivated()->create();

        $this->patch(route('titles.unretire', $title));
    }
}
