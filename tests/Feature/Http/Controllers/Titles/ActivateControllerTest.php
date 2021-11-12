<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class ActivateControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function invoke_activates_an_unactivated_title_and_redirects()
    {
        $title = Title::factory()->unactivated()->create();

        $this->assertEquals(TitleStatus::UNACTIVATED, $title->status);

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ActivateController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertCount(1, $title->activations);
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function invoke_activates_a_future_activated_title_and_redirects()
    {
        $title = Title::factory()->withFutureActivation()->create();
        $startedAt = $title->activations->last()->started_at;

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ActivateController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) use ($startedAt) {
            $this->assertTrue($title->currentActivation->started_at->lt($startedAt));
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function invoke_activates_an_inactive_title_and_redirects()
    {
        $title = Title::factory()->inactive()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ActivateController::class], $title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($title->fresh(), function ($title) {
            $this->assertEquals(TitleStatus::ACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_activate_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([ActivateController::class], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_activate_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->patch(action([ActivateController::class], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nonactivatableTitleTypes
     */
    public function invoke_throws_exception_for_activating_a_non_activatable_title($factoryState)
    {
        $this->expectException(CannotBeActivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([ActivateController::class], $title));
    }

    public function nonactivatableTitleTypes()
    {
        return [
            'active title' => ['active'],
            'retired title' => ['retired'],
        ];
    }
}
