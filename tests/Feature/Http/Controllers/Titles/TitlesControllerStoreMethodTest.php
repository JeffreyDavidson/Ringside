<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use Tests\Factories\TitleRequestDataFactory;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class TitlesControllerStoreMethodTest extends TestCase
{
    /**
     * @test
     */
    public function create_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([TitlesController::class, 'create']))
            ->assertViewIs('titles.create')
            ->assertViewHas('title', new Title);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_creating_a_title()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([TitlesController::class, 'create']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function guests_cannot_view_the_form_for_creating_a_title()
    {
        $this
            ->get(action([TitlesController::class, 'create']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function store_creates_a_title_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([TitlesController::class, 'create']))
            ->post(action([TitlesController::class, 'store']), TitleRequestDataFactory::new()->create([
                'name' => 'Example Title',
                'activated_at' => null,
            ]))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap(Title::first(), function ($title) {
            $this->assertEquals('Example Title', $title->name);
        });
    }

    /**
     * @test
     */
    public function an_activation_is_not_created_for_the_title_if_activated_at_is_filled_in_request()
    {
        $this
            ->actAs(Role::administrator())
            ->from(action([TitlesController::class, 'create']))
            ->post(
                action([TitlesController::class, 'store']),
                TitleRequestDataFactory::new()->create(['activated_at' => null])
            );

        tap(Title::first(), function ($title) {
            $this->assertCount(0, $title->activations);
        });
    }

    /**
     * @test
     */
    public function an_activation_is_created_for_the_title_if_activated_at_is_filled_in_request()
    {
        $activatedAt = now()->toDateTimeString();

        $this
            ->actAs(Role::administrator())
            ->from(action([TitlesController::class, 'create']))
            ->post(
                action([TitlesController::class, 'store']),
                TitleRequestDataFactory::new()->create(['activated_at' => $activatedAt])
            );

        tap(Title::first(), function ($title) use ($activatedAt) {
            $this->assertCount(1, $title->activations);
            $this->assertEquals($activatedAt, $title->activations->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_a_title()
    {
        $this
            ->actAs(Role::basic())
            ->from(action([TitlesController::class, 'create']))
            ->post(action([TitlesController::class, 'store']), TitleRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function guests_cannot_create_a_title()
    {
        $this
            ->from(action([TitlesController::class, 'create']))
            ->post(action([TitlesController::class, 'store']), TitleRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
