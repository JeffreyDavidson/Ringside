<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class RetireTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_retired_title_cannot_be_retired_again()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertForbidden();
    }

    /** @test */
    public function a_pending_introduction_title_cannot_be_retired()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->states('pending-introduction')->create();

        $response = $this->put(route('titles.retire', $title));

        $response->assertForbidden();
    }
}
