<?php

namespace Tests\Feature\Admin\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group admins
 */
class RetireTitleSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_retire_a_bookable_title()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('bookable')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertRedirect(route('titles.index'));
        $this->assertEquals(now()->toDateTimeString(), $title->fresh()->retirement->started_at);
    }
}
