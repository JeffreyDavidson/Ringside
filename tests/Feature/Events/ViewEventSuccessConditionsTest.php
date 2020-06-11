<?php

namespace Tests\Feature\Events;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Factories\EventFactory;
use Tests\Factories\VenueFactory;
use Tests\TestCase;

/**
 * @group events
 */
class ViewEventSuccessConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider adminRoles
     */
    public function administrators_can_view_an_event_page($adminRoles)
    {
        $this->actAs($adminRoles);
        $event = EventFactory::new()->create();

        $response = $this->showRequest($event);

        $response->assertViewIs('events.show');
        $this->assertTrue($response->data('event')->is($event));
    }

    public function adminRoles()
    {
        return [
            [Role::ADMINISTRATOR],
            [Role::SUPER_ADMINISTRATOR],
        ];
    }
}
