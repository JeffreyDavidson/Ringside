<?php

declare(strict_types=1);

use App\Livewire\Matches\Tables\MatchesTable;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

describe('MatchesTable Rendering', function () {
    it('can render matches table', function () {
        $event = Event::factory()->create();
        
        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('displays matches in table', function () {
        $event = Event::factory()->create();
        $matchType = MatchType::factory()->create(['name' => 'Singles Match']);
        $match = EventMatch::factory()
            ->for($event)
            ->for($matchType)
            ->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles Match');
    });

    it('displays match competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $wrestler1 = Wrestler::factory()->create(['name' => 'John Cena']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);
        
        $match->wrestlers()->attach($wrestler1, ['side_number' => 1]);
        $match->wrestlers()->attach($wrestler2, ['side_number' => 2]);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('John Cena')
            ->assertSee('The Rock');
    });

    it('displays match referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $referee = Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        
        $match->referees()->attach($referee);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Earl Hebner');
    });

    it('displays championship titles', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $title = Title::factory()->create(['name' => 'WWE Championship']);
        
        $match->titles()->attach($title);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('WWE Championship');
    });
});

describe('MatchesTable Search and Filtering', function () {
    it('can search matches by match type', function () {
        $event = Event::factory()->create();
        $championshipType = MatchType::factory()->create(['name' => 'Championship Match']);
        $regularType = MatchType::factory()->create(['name' => 'Regular Match']);
        
        EventMatch::factory()->for($event)->for($championshipType)->create();
        EventMatch::factory()->for($event)->for($regularType)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->set('search', 'Championship')
            ->assertSee('Championship Match')
            ->assertDontSee('Regular Match');
    });

    it('can filter matches by event', function () {
        $event1 = Event::factory()->create(['name' => 'WrestleMania']);
        $event2 = Event::factory()->create(['name' => 'SummerSlam']);
        
        $event1MatchType = MatchType::factory()->create(['name' => 'Main Event']);
        $event2MatchType = MatchType::factory()->create(['name' => 'Opening Match']);
        
        EventMatch::factory()->for($event1)->for($event1MatchType)->create();
        EventMatch::factory()->for($event2)->for($event2MatchType)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event1->id])
            ->assertSee('Main Event')
            ->assertDontSee('Opening Match');
    });

    it('can filter matches by match type', function () {
        $event = Event::factory()->create();
        $singlesType = MatchType::factory()->create(['name' => 'Singles']);
        $tagTeamType = MatchType::factory()->create(['name' => 'Tag Team']);
        
        EventMatch::factory()->for($event)->for($singlesType)->create();
        EventMatch::factory()->for($event)->for($tagTeamType)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Singles')
            ->assertSee('Tag Team');
    });

    it('displays competitor names in matches', function () {
        $event = Event::factory()->create();
        $match1 = EventMatch::factory()->for($event)->create();
        $match2 = EventMatch::factory()->for($event)->create();
        
        $wrestler1 = Wrestler::factory()->create(['name' => 'Stone Cold']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);
        
        $match1->wrestlers()->attach($wrestler1, ['side_number' => 1]);
        $match2->wrestlers()->attach($wrestler2, ['side_number' => 1]);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Stone Cold')
            ->assertSee('The Rock');
    });
});

describe('MatchesTable Complex Relationships', function () {
    it('displays matches with multiple competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $wrestler1 = Wrestler::factory()->create(['name' => 'Wrestler One']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'Wrestler Two']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
        
        $match->wrestlers()->attach($wrestler1, ['side_number' => 1]);
        $match->wrestlers()->attach($wrestler2, ['side_number' => 2]);
        $match->tagTeams()->attach($tagTeam, ['side_number' => 3]);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Wrestler One')
            ->assertSee('Wrestler Two')
            ->assertSee('Tag Team');
    });

    it('displays championship matches correctly', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $title1 = Title::factory()->create(['name' => 'World Championship']);
        $title2 = Title::factory()->create(['name' => 'Tag Team Championship']);
        
        $match->titles()->attach($title1);
        $match->titles()->attach($title2);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('World Championship')
            ->assertSee('Tag Team Championship');
    });

    it('displays matches with multiple referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $referee1 = Referee::factory()->create(['first_name' => 'Referee', 'last_name' => 'One']);
        $referee2 = Referee::factory()->create(['first_name' => 'Referee', 'last_name' => 'Two']);
        
        $match->referees()->attach($referee1);
        $match->referees()->attach($referee2);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Referee One')
            ->assertSee('Referee Two');
    });

    it('handles matches with no competitors gracefully', function () {
        $event = Event::factory()->create();
        $matchType = MatchType::factory()->create(['name' => 'Special Match']);
        EventMatch::factory()->for($event)->for($matchType)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Special Match');
    });

    it('handles matches with no referees gracefully', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        $match->wrestlers()->attach($wrestler, ['side_number' => 1]);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Test Wrestler')
            ->assertSee('Test Wrestler');
    });
});

describe('MatchesTable Performance', function () {
    it('handles large datasets efficiently', function () {
        $event = Event::factory()->create();
        $matchType = MatchType::factory()->create();
        
        // Create multiple matches with relationships
        $matches = EventMatch::factory()
            ->for($event)
            ->for($matchType)
            ->count(20)
            ->create();

        $wrestlers = Wrestler::factory()->count(10)->create();
        $referees = Referee::factory()->count(5)->create();
        
        // Attach relationships to matches
        foreach ($matches as $index => $match) {
            $match->wrestlers()->attach($wrestlers[$index % 10], ['side_number' => 1]);
            $match->referees()->attach($referees[$index % 5]);
        }

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('eager loads necessary relationships', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        $referee = Referee::factory()->create(['first_name' => 'Test', 'last_name' => 'Referee']);
        $title = Title::factory()->create(['name' => 'Test Title']);
        
        $match->wrestlers()->attach($wrestler, ['side_number' => 1]);
        $match->referees()->attach($referee);
        $match->titles()->attach($title);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk()
            ->assertSee('Test Wrestler')
            ->assertSee('Test Referee')
            ->assertSee('Test Title');
    });
});

describe('MatchesTable Pagination', function () {
    it('handles pagination correctly', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->count(25)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('maintains search across pagination', function () {
        $event = Event::factory()->create();
        $championshipType = MatchType::factory()->create(['name' => 'Championship Match']);
        $regularType = MatchType::factory()->create(['name' => 'Regular Match']);
        
        EventMatch::factory()->for($event)->for($championshipType)->count(15)->create();
        EventMatch::factory()->for($event)->for($regularType)->count(15)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->set('search', 'Championship')
            ->assertSee('Championship Match')
            ->assertDontSee('Regular Match');
    });
});

describe('MatchesTable Sorting', function () {
    it('can sort matches by different columns', function () {
        $event = Event::factory()->create();
        $matchA = EventMatch::factory()->for($event)->create(['preview' => 'A match']);
        $matchZ = EventMatch::factory()->for($event)->create(['preview' => 'Z match']);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSeeInOrder(['A match', 'Z match']);
    });

    it('can sort matches by event date', function () {
        $eventOld = Event::factory()->create(['date' => '2023-01-01']);
        $eventNew = Event::factory()->create(['date' => '2024-01-01']);
        
        EventMatch::factory()->for($eventOld)->create(['preview' => 'Old match']);
        EventMatch::factory()->for($eventNew)->create(['preview' => 'New match']);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Old match')
            ->assertSee('New match');
    });
});

describe('MatchesTable Actions', function () {
    it('displays match actions for authorized users', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertOk();
    });

    it('handles match action integration', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $component = Livewire::test(MatchesTable::class, ['eventId' => $event->id]);
        $component->assertOk();
        $component->assertSee($match->preview);
    });
});

describe('MatchesTable Event Integration', function () {
    it('displays matches grouped by event', function () {
        $event1 = Event::factory()->create(['name' => 'Event One']);
        $event2 = Event::factory()->create(['name' => 'Event Two']);
        
        EventMatch::factory()->for($event1)->create(['preview' => 'Event One Match']);
        EventMatch::factory()->for($event2)->create(['preview' => 'Event Two Match']);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Event One Match')
            ->assertSee('Event Two Match');
    });

    it('handles matches from past and future events', function () {
        $pastEvent = Event::factory()->past()->create();
        $futureEvent = Event::factory()->future()->create();
        
        EventMatch::factory()->for($pastEvent)->create(['preview' => 'Past match']);
        EventMatch::factory()->for($futureEvent)->create(['preview' => 'Future match']);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertSee('Past match')
            ->assertSee('Future match');
    });
});

describe('MatchesTable Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(MatchesTable::class, ['eventId' => $event->id])
            ->assertUnauthorized();
    });
});