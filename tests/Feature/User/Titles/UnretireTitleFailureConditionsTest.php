<?php

namespace Tests\Feature\User\Titles;

use Tests\TestCase;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group titles
 * @group users
 */
class UnretireTitleFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_unretire_a_retired_title()
    {
        $this->actAs('basic-user');
        $title = factory(Title::class)->states('retired')->create();

        $response = $this->put(route('titles.unretire', $title));

        $response->assertForbidden();
    }
}
