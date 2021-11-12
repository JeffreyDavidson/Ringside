<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\RestoreController;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class RestoreControllerTest extends TestCase
{
    use RefreshDatabase;

    public Venue $venue;

    public function setUp(): void
    {
        parent::setUp();

        $this->venue = Venue::factory()->softDeleted()->create();
    }

    /**
     * @test
     */
    public function invoke_restores_a_deleted_venue_and_redirects()
    {
        $this
            ->actAs(Role::administrator())
            ->patch(action([RestoreController::class], $this->venue))
            ->assertRedirect(action([VenuesController::class, 'index']));

        $this->assertNull($this->venue->fresh()->deleted_at);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_restore_a_venue()
    {
        $this
            ->actAs(Role::basic())
            ->patch(action([RestoreController::class], $this->venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_restore_a_venue()
    {
        $this
            ->patch(action([RestoreController::class], $this->venue))
            ->assertRedirect(route('login'));
    }
}
