<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class UnretireTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_title_cannot_unretire()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('bookable')->create();

        $response = $this->put(route('titles.unretire', $title));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_title_cannot_unretire()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('pending-introduction')->create();

        $response = $this->put(route('titles.unretire', $title));

        $response->assertForbidden();
    }
}
