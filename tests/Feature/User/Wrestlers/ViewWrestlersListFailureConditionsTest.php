<?php

namespace Tests\Feature\User\Wrestlers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group wrestlers
 * @group users
 */
class ViewWrestlersListSFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_wrestlers_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('wrestlers.index'));

        $response->assertForbidden();
    }
}
