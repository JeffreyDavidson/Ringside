<?php

namespace Tests\Feature\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\StoreRequest;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @group titles
 */
class CreateTitleTest extends TestCase
{
    use RefreshDatabase, AdditionalAssertions;

    /**
     * Valid parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Name Title',
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_creating_a_title()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->createRequest('title');

        $response->assertViewIs('titles.create');
        $response->assertViewHas('title', new Title);
    }

    /** @test */
    public function an_administrator_can_create_a_title()
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->storeRequest('title', $this->validParams());

        $response->assertRedirect(route('titles.index'));
        tap(Title::first(), function ($title) use ($now) {
            $this->assertEquals('Example Name Title', $title->name);
        });
    }

    /** @test */
    public function a_title_created_without_an_activated_at_filled_does_not_have_an_activation()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('title', $this->validParams(['activated_at' => null]));

        tap(Title::first(), function ($title) {
            $this->assertCount(0, $title->activations);
        });
    }

    /** @test */
    public function a_title_created_when_activated_at_is_filled_has_an_activation()
    {
        $activatedAt = now()->toDateTimeString();

        $this->actAs(Role::ADMINISTRATOR);

        $this->storeRequest('titles', $this->validParams(['activated_at' => $activatedAt]));

        tap(Title::first(), function ($title) use ($activatedAt) {
            $this->assertCount(1, $title->activations);
            $this->assertEquals($activatedAt, $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_creating_a_title()
    {
        $this->actAs(Role::BASIC);

        $response = $this->createRequest('titles');

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_create_a_title()
    {
        $this->actAs(Role::BASIC);

        $response = $this->storeRequest('titles', $this->validParams());

        $response->assertForbidden();
    }

    /** @test */
    public function users_must_be_logged_in_to_view_create_title_form()
    {
        $this->assertActionUsesMiddleware(TitlesController::class, 'create', 'auth');
    }

    /** @test */
    public function users_must_be_logged_in_to_save_a_title()
    {
        $this->assertActionUsesMiddleware(TitlesController::class, 'store', 'auth');
    }

    /** @test */
    public function store_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            TitlesController::class,
            'store',
            StoreRequest::class
        );
    }
}
