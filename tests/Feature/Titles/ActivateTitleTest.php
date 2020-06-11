<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Requests\Titles\ActivateRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\ActivationFactory;
use Tests\Factories\TitleFactory;
use Tests\TestCase;

/**
 * @group titles
 */
class ActivateTitleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function invoke_method_activates_a_future_activation_title()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);
        $title = TitleFactory::new()
            ->futureActivation(
                ActivationFactory::new()->started($now->addDays(2))
            )
            ->create();

        $response = $this->activateRequest($title);

        $response->assertRedirect(route('titles.index'));
        tap($title->fresh(), function ($title) use ($now) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
            $this->assertCount(1, $title->activations);
            $this->assertEquals($now->toDateTimeString(), $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_activate_a_future_activation_title()
    {
        $this->actAs(Role::BASIC);
        $title = TitleFactory::new()->futureActivation()->create();

        $response = $this->activateRequest($title);

        $response->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_activate_a_future_activation_title()
    {
        $title = TitleFactory::new()->futureActivation()->create();

        $response = $this->activateRequest($title);

        $response->assertRedirect(route('login'));
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
}
