<?php

namespace Tests\Feature\User\Referees;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group referees
 * @group users
 */
class ViewRefereesListFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_basic_user_cannot_view_referees_page()
    {
        $this->actAs('basic-user');

        $response = $this->get(route('referees.index'));

        $response->assertForbidden();
    }
}
