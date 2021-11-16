<?php

namespace Tests\Feature\Http\Controllers\EventMatches;

use App\Enums\Role;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Models\Event;
use App\Models\Referee;
use App\Models\Title;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;
use Tests\Factories\EventMatchRequestDataFactory;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventMatchControllerStoreMethodTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->seed(MatchTypesTableSeeder::class);
    }

    /**
     * @test
     */
    public function store_creates_matches_for_an_event_and_redirects()
    {
        $event = Event::factory()->create();
        $referee = Referee::factory()->create();
        $wrestlerA = Wrestler::factory()->create();
        $wrestlerB = Wrestler::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create([
                    'match_type_id' => 1,
                    'titles' => [],
                    'referees' => [$referee->id],
                    'competitors' => [$wrestlerA->id, $wrestlerB->id],
                    'preview' => 'This is a general match preview.',
                ])
            );

        $this->assertCount(1, $event->matches);
        tap($event->matches->first(), function ($match) use ($referee) {
            $this->assertEquals(1, $match->match_type_id);
            $this->assertCount(0, $match->titles);
            $this->assertCount(1, $match->referees);
            $this->assertCollectionHas($match->referees, $referee);
            $this->assertEquals('This is a general match preview.', $match->preview);
        });
    }

    /**
     * @test
     */
    public function store_creates_a_title_match_for_an_event_and_redirects()
    {
        $event = Event::factory()->create();
        $referee = Referee::factory()->create();
        $title = Title::factory()->create();
        $wrestlerA = Wrestler::factory()->create();
        $wrestlerB = Wrestler::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create([
                    'match_type_id' => 1,
                    'titles' => [$title->id],
                    'referees' => [$referee->id],
                    'competitors' => [$wrestlerA->id, $wrestlerB->id],
                ])
            );

        tap($event->matches->first(), function ($match) use ($title) {
            $this->assertCount(1, $match->titles);
            $this->assertCollectionHas($match->titles, $title);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_create_matches_for_an_event()
    {
        $event = Event::factory()->create();

        $this
            ->actAs(Role::basic())
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create([])
            )
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_create_a_event()
    {
        $event = Event::factory()->create();

        $this
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create()
            )
            ->assertRedirect(route('login'));
    }
}
