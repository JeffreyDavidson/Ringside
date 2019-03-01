<?php

namespace Tests\Feature\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeleteRefereeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_delete_a_referee()
    {
        $this->actAs('administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $this->assertSoftDeleted('referees', ['first_name' => $referee->first_name, 'last_name' => $referee->last_name]);
    }

    /** @test */
    public function a_basic_user_cannot_delete_a_referee()
    {
        $this->actAs('basic-user');
        $referee = factory(Referee::class)->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_delete_a_referee()
    {
        $referee = factory(Referee::class)->create();

        $response = $this->delete(route('referees.destroy', $referee));

        $response->assertRedirect('/login');
    }
}
