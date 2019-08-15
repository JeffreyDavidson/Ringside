<?php

namespace Tests\Feature\User\Stables;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group stables
 * @group users
 */
class ViewStablesListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_stables_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('stables.index'));

        $response->assertForbidden();
    }
}
