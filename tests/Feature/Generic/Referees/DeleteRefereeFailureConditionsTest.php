<?php

namespace Tests\Feature\Generic\Referees;

use App\Models\Referee;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group generics
 */
class DeleteRefereeFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_deleted_wrestler_cannot_be_deleted()
    {
        $this->actAs('administrator');
        $wrestler = factory(Referee::class)->create()->delete();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertNotFound();
    }
}
