<?php

namespace Tests\Feature\Http\Controllers\Stables;

use App\Enums\Role;
use App\Enums\StableStatus;
use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group stables
 * @group feature-sstables
 * @group roster
 * @group feature-sroster
 */
class UnretireControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider administrators
     */
    public function invoke_unretires_a_title($administrators)
    {
        $now = now();
        Carbon::setTestNow($now);

        $this->actAs($administrators);
        $stable = Stable::factory()->retired()->create();

        $response = $this->unretireRequest($stable);

        $response->assertRedirect(route('stables.index'));
        tap($stable->fresh(), function ($stable) use ($now) {
            $this->assertEquals(StableStatus::ACTIVE, $stable->status);
            $this->assertCount(1, $stable->retirements);
            $this->assertEquals($now->toDateTimeString(), $stable->fresh()->retirements()->latest()->first()->ended_at);
        });
    }

    /** @test */
    public function a_basic_user_cannot_unretire_a_stable()
    {
        $this->actAs(Role::BASIC);
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertForbidden();
    }

    /** @test */
    public function a_guest_cannot_unretire_a_stable()
    {
        $stable = Stable::factory()->create();

        $this->retireRequest($stable)->assertRedirect(route('login'));
    }
}
