<?php

namespace Tests\Feature\Http\Controllers\Titles;

use App\Enums\Role;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use Tests\TestCase;

/**
 * @group titles
 * @group feature-titles
 */
class RestoreControllerTest extends TestCase
{
    public Title $title;

    public function setUp(): void
    {
        parent::setUp();

        $this->title = Title::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_deleted_title_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->title))
            ->assertRedirect(action([TitlesController::class, 'index']));

        tap($this->title->fresh(), function ($title) {
            $this->assertNull($title->deleted_at);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_title()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->title))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_title()
    {
        $this
            ->patch(action([RestoreController::class], $this->title))
            ->assertRedirect(route('login'));
    }
}
