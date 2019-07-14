<?php

namespace Tests\Feature\Generic\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group generics
 */
class RestoreTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_non_deleted_title_cannot_be_restored()
    {
        $this->actAs('administrator');
        $title = factory(Title::class)->create();

        $response = $this->put(route('titles.restore', $title));

        $response->assertNotFound();
    }
}
