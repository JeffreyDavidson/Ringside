<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class RestoreControllerTest extends TestCase
{
    public Stable $stable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stable = Stable::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_stable_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->stable))
            ->assertRedirect(action([StablesController::class, 'index']));

        $this->assertNull($this->stable->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_stable()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->stable))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_stable()
    {
        $this->patch(action([RestoreController::class], $this->stable))
            ->assertRedirect(route('login'));
    }
}
