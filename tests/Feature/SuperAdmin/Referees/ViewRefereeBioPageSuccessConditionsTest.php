<?php

namespace Tests\Feature\SuperAdmin\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group superadmins
 */
class ViewRefereeBioPageSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_super_administrator_can_view_a_referee_profile()
    {
        $this->actAs('super-administrator');
        $referee = factory(Referee::class)->create();

        $response = $this->get(route('referees.show', ['referee' => $referee]));

        $response->assertViewIs('referees.show');
        $this->assertTrue($response->data('referee')->is($referee));
    }
}
