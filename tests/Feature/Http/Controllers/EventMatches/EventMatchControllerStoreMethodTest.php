<?php

namespace Tests\Feature\Http\Controllers\EventMatches;

use App\Enums\Role;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Models\Event;
use App\Models\Referee;
use App\Models\Title;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Database\Eloquent\Collection;
use Tests\Factories\EventMatchRequestDataFactory;
use Tests\TestCase;

/**
 * @group events
 * @group feature-events
 */
class EventMatchControllerStoreMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MatchTypesTableSeeder::class);
    }

    /**
     * @test
     */
    public function store_creates_a_non_title_match_for_an_event_and_redirects()
    {
        $event = Event::factory()->scheduled()->create();
        $referee = Referee::factory()->bookable()->create();
        $wrestlerA = Wrestler::factory()->bookable()->create();
        $wrestlerB = Wrestler::factory()->bookable()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create([
                    'match_type_id' => 1,
                    'titles' => [],
                    'referees' => [$referee->id],
                    'competitors' => [
                        ['competitor_id' => $wrestlerA->id, 'competitor_type' => 'wrestler'],
                        ['competitor_id' => $wrestlerB->id, 'competitor_type' => 'wrestler'],
                    ],
                    'preview' => 'This is a general match preview.',
                ])
            );

        $this->assertCount(1, $event->matches);
        tap($event->matches->first(), function ($match) use ($referee, $wrestlerA, $wrestlerB) {
            $this->assertEquals(1, $match->match_type_id);
            $this->assertCount(0, $match->titles);
            $this->assertCount(1, $match->referees);
            $this->assertCollectionHas($match->referees, $referee);
            $this->assertInstanceOf(Collection::class, $match->competitors);
            $this->assertCount(2, $match->competitors->groupBySide());
            $this->assertCount(1, $match->competitors->groupBySide()[0]->groupByType());
            $this->assertCollectionHas($wrestlerA, $match->competitors->groupBySide()[0]->groupByType());
            $this->assertCount(1, $match->competitors[1]->wrestlers);
            $this->assertCollectionHas($wrestlerB, $match->competitors[1]->wrestlers);
            $this->assertCount(2, $match->wrestlers);
            $this->assertCollectionHas($match->wrestlers, $wrestlerA);
            $this->assertCollectionHas($match->wrestlers, $wrestlerB);
            $this->assertEquals('This is a general match preview.', $match->preview);
        });
    }

    /**
     * @test
     */
    public function store_creates_a_title_match_for_an_event_and_redirects()
    {
        $event = Event::factory()->scheduled()->create();
        $title = Title::factory()->active()->create();

        $this
            ->actAs(Role::administrator())
            ->from(action([EventMatchesController::class, 'create'], $event))
            ->post(
                action([EventMatchesController::class, 'store'], $event),
                EventMatchRequestDataFactory::new()->create([
                    'titles' => [$title->id],
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
