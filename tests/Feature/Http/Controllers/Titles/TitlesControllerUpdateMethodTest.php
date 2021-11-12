<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\TitleRequestDataFactory;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class TitlesControllerUpdateMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([TitlesController::class, 'edit'], $title))
            ->assertViewIs('titles.edit')
            ->assertViewHas('title', $title);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_title()
    {
        $title = Title::factory()->create();

        $this->actAs(Role::BASIC)
            ->get(action([TitlesController::class, 'edit'], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->get(action([TitlesController::class, 'edit'], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_a_title()
    {
        $title = Title::factory()->create(['name' => 'Old Name Title']);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create([
                    'name' => 'New Name Title',
                ])
            )
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertEquals('New Name Title', $title->name);
        });
    }

    /**
     * @test
     */
    public function update_can_activate_an_unactivated_title_when_activated_at_is_filled()
    {
        $title = Title::factory()->unactivated()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create(['activated_at' => now()->toDateTimeString()])
            )
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertCount(1, $title->activations);
        });
    }

    /**
     * @test
     */
    public function update_can_activate_a_future_activated_title_when_activated_at_is_filled()
    {
        $title = Title::factory()->withFutureActivation()->create();
        $startDate = $title->activations->last()->started_at->toDateTimeString();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create()
            )
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) use ($startDate) {
            $this->assertCount(1, $title->activations);
            $this->assertEquals($startDate, $title->activations()->first()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function update_cannot_activate_an_inactive_title()
    {
        $title = Title::factory()->inactive()->create();
        $startDate = $title->activations->last()->started_at->toDateTimeString();

        $this->actAs(Role::ADMINISTRATOR)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create()
            )
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) use ($startDate) {
            $this->assertCount(1, $title->activations);
            $this->assertSame($startDate, $title->activations->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function updating_cannot_activate_an_active_title()
    {
        $title = Title::factory()->active()->create();
        $startDate = $title->activations->last()->started_at->toDateTimeString();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create([
                    'activated_at' => now()->toDateTimeString(),
                ])
            )
            ->assertSessionHasErrors(['activated_at']);

        tap($title->fresh(), function ($title) use ($startDate) {
            $this->assertCount(1, $title->activations);
            $this->assertSame($startDate, $title->activations->last()->started_at->toDateTimeString());
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create()
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->from(action([TitlesController::class, 'edit'], $title))
            ->put(
                action([TitlesController::class, 'update'], $title),
                TitleRequestDataFactory::new()->withTitle($title)->create()
            )
            ->assertRedirect(route('login'));
    }
}
