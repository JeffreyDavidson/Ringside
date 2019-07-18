<?php

namespace Tests\Feature\Generic\Wrestlers;

use Tests\TestCase;
use App\Models\Wrestler;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group generics
 */
class DeleteWrestlerFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_already_deleted_wrestler_cannot_be_deleted()
    {
        $this->actAs('administrator');
        $wrestler = factory(Wrestler::class)->create()->delete();

        $response = $this->delete(route('wrestlers.destroy', $wrestler));

        $response->assertNotFound();
    }
}
