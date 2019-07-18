<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class ActivateTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_bookable_title_cannot_be_activated()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('bookable')->create();

        $response = $this->put(route('titles.activate', $title));

        $response->assertForbidden();
    }
}
