<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Enums\TitleStatus;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Models\Title;
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
     */
    public function invoke_deactivates_an_active_title_and_redirects()
    {
        $title = Title::factory()->active()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title))
            ->assertRedirect(route('titles.index'));

        tap($title->fresh(), function ($title) {
            $this->assertNotNull($title->activations->last()->ended_at);
            $this->assertEquals(TitleStatus::INACTIVE, $title->status);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->actAs(Role::BASIC)
            ->patch(action([DeactivateController::class], $title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_deactivates_a_title()
    {
        $title = Title::factory()->create();

        $this
            ->patch(action([DeactivateController::class], $title))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     * @dataProvider nondeactivatableTitleTypes
     */
    public function invoke_throws_exception_for_deactivating_a_non_deactivatable_title($factoryState)
    {
        $this->expectException(CannotBeDeactivatedException::class);
        $this->withoutExceptionHandling();

        $title = Title::factory()->{$factoryState}()->create();

        $this
            ->actAs(Role::ADMINISTRATOR)
            ->patch(action([DeactivateController::class], $title));
    }

    public function nondeactivatableTitleTypes()
    {
        return [
            'unactivated title' => ['unactivated'],
            'with future activation title' => ['withFutureActivation'],
            'inactive title' => ['inactive'],
            'retired title' => ['retired'],
        ];
    }
}
