<?php

namespace Tests\Feature\Http\Controllers\Referees;

use App\Enums\Role;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RestoreController;
use App\Models\Referee;
use Tests\TestCase;

/**
 * @group referees
 * @group feature-referees
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    public Referee $referee;

    public function setUp(): void
    {
        parent::setUp();

        $this->referee = Referee::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_soft_deleted_referee_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->referee))
            ->assertRedirect(action([RefereesController::class, 'index']));

        $this->assertNull($this->referee->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_referee()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->referee))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_referee()
    {
        $this
            ->patch(action([RestoreController::class], $this->referee))
            ->assertRedirect(route('login'));
    }
}
