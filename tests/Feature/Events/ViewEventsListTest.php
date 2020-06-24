<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group events
 */
class ViewEventsListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function index_renders_the_correct_view()
    {
        $this->actAs(Role::ADMINISTRATOR);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertViewIs('events.index');
    }
}
