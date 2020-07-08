<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Requests\Titles\RetireRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TitleFactory;
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
    public function invoke_retires_a_title($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $title = TitleFactory::new()->active()->create();

        $response = $this->retireRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::RETIRED, $title->status);
            $this->assertCount(1, $title->retirements);
            $this->assertEquals($now->toDateTimeString(), $title->fresh()->currentRetirement->started_at);
        });
    }

    /** @test */
    public function a_basic_user_cannot_retire_a_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->create();

        $response = $this->retireRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_title()
    {
        $title = TitleFactory::new()->create();

        $this->retireRequest($title)->assertRedirect(route('login'));
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
}
