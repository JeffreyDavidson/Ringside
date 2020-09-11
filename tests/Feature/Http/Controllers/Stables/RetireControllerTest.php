<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Models\Stable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-stables
 * @group roster
 * @group feature-roster
 */
class RetireControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_retire_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_retire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertRedirect(route('login'));
    }
}
