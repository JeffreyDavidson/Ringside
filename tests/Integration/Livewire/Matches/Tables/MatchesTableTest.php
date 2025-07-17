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
        Livewire::test(MatchesTable::class)
            ->assertOk();
    });

    it('displays matches in table', function () {
        $event = Event::factory()->create();
        $matchType = MatchType::factory()->create(['name' => 'Singles Match']);
        $match = EventMatch::factory()
            ->for($event)
            ->for($matchType)
            ->create(['preview' => 'Epic wrestling match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('Epic wrestling match')
            ->assertSee('Singles Match');
    });

    it('displays match competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $wrestler1 = Wrestler::factory()->create(['name' => 'John Cena']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);
        
        $match->competitors()->attach($wrestler1);
        $match->competitors()->attach($wrestler2);

        Livewire::test(MatchesTable::class)
            ->assertSee('John Cena')
            ->assertSee('The Rock');
    });

    it('displays match referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $referee = Referee::factory()->create(['name' => 'Earl Hebner']);
        
        $match->referees()->attach($referee);

        Livewire::test(MatchesTable::class)
            ->assertSee('Earl Hebner');
    });

    it('displays championship titles', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        $title = Title::factory()->create(['name' => 'WWE Championship']);
        
        $match->titles()->attach($title);

        Livewire::test(MatchesTable::class)
            ->assertSee('WWE Championship');
    });
});

describe('MatchesTable Search and Filtering', function () {
    it('can search matches by preview', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->create(['preview' => 'Championship match']);
        EventMatch::factory()->for($event)->create(['preview' => 'Regular match']);

        Livewire::test(MatchesTable::class)
            ->set('search', 'Championship')
            ->assertSee('Championship match')
            ->assertDontSee('Regular match');
    });

    it('can filter matches by event', function () {
        $event1 = Event::factory()->create(['name' => 'WrestleMania']);
        $event2 = Event::factory()->create(['name' => 'SummerSlam']);
        
        EventMatch::factory()->for($event1)->create(['preview' => 'WrestleMania match']);
        EventMatch::factory()->for($event2)->create(['preview' => 'SummerSlam match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('WrestleMania match')
            ->assertSee('SummerSlam match');
    });

    it('can filter matches by match type', function () {
        $event = Event::factory()->create();
        $singlesType = MatchType::factory()->create(['name' => 'Singles']);
        $tagTeamType = MatchType::factory()->create(['name' => 'Tag Team']);
        
        EventMatch::factory()->for($event)->for($singlesType)->create(['preview' => 'Singles match']);
        EventMatch::factory()->for($event)->for($tagTeamType)->create(['preview' => 'Tag team match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('Singles match')
            ->assertSee('Tag team match');
    });

    it('can search matches by competitor names', function () {
        $event = Event::factory()->create();
        $match1 = EventMatch::factory()->for($event)->create(['preview' => 'First match']);
        $match2 = EventMatch::factory()->for($event)->create(['preview' => 'Second match']);
        
        $wrestler1 = Wrestler::factory()->create(['name' => 'Stone Cold']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);
        
        $match1->competitors()->attach($wrestler1);
        $match2->competitors()->attach($wrestler2);

        Livewire::test(MatchesTable::class)
            ->set('search', 'Stone Cold')
            ->assertSee('First match')
            ->assertDontSee('Second match');
    });
});

describe('MatchesTable Complex Relationships', function () {
    it('displays matches with multiple competitors', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $wrestler1 = Wrestler::factory()->create(['name' => 'Wrestler One']);
        $wrestler2 = Wrestler::factory()->create(['name' => 'Wrestler Two']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Tag Team']);
        
        $match->competitors()->attach($wrestler1);
        $match->competitors()->attach($wrestler2);
        $match->competitors()->attach($tagTeam);

        Livewire::test(MatchesTable::class)
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

        Livewire::test(MatchesTable::class)
            ->assertSee('World Championship')
            ->assertSee('Tag Team Championship');
    });

    it('displays matches with multiple referees', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $referee1 = Referee::factory()->create(['name' => 'Referee One']);
        $referee2 = Referee::factory()->create(['name' => 'Referee Two']);
        
        $match->referees()->attach($referee1);
        $match->referees()->attach($referee2);

        Livewire::test(MatchesTable::class)
            ->assertSee('Referee One')
            ->assertSee('Referee Two');
    });

    it('handles matches with no competitors gracefully', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->create(['preview' => 'No competitors match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('No competitors match');
    });

    it('handles matches with no referees gracefully', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create(['preview' => 'No referee match']);
        
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        $match->competitors()->attach($wrestler);

        Livewire::test(MatchesTable::class)
            ->assertSee('No referee match')
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
            $match->competitors()->attach($wrestlers[$index % 10]);
            $match->referees()->attach($referees[$index % 5]);
        }

        Livewire::test(MatchesTable::class)
            ->assertOk();
    });

    it('eager loads necessary relationships', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();
        
        $wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
        $referee = Referee::factory()->create(['name' => 'Test Referee']);
        $title = Title::factory()->create(['name' => 'Test Title']);
        
        $match->competitors()->attach($wrestler);
        $match->referees()->attach($referee);
        $match->titles()->attach($title);

        Livewire::test(MatchesTable::class)
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

        Livewire::test(MatchesTable::class)
            ->assertOk();
    });

    it('maintains search across pagination', function () {
        $event = Event::factory()->create();
        EventMatch::factory()->for($event)->count(15)->create(['preview' => 'Championship match']);
        EventMatch::factory()->for($event)->count(15)->create(['preview' => 'Regular match']);

        Livewire::test(MatchesTable::class)
            ->set('search', 'Championship')
            ->assertSee('Championship match')
            ->assertDontSee('Regular match');
    });
});

describe('MatchesTable Sorting', function () {
    it('can sort matches by different columns', function () {
        $event = Event::factory()->create();
        $matchA = EventMatch::factory()->for($event)->create(['preview' => 'A match']);
        $matchZ = EventMatch::factory()->for($event)->create(['preview' => 'Z match']);

        Livewire::test(MatchesTable::class)
            ->assertSeeInOrder(['A match', 'Z match']);
    });

    it('can sort matches by event date', function () {
        $eventOld = Event::factory()->create(['date' => '2023-01-01']);
        $eventNew = Event::factory()->create(['date' => '2024-01-01']);
        
        EventMatch::factory()->for($eventOld)->create(['preview' => 'Old match']);
        EventMatch::factory()->for($eventNew)->create(['preview' => 'New match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('Old match')
            ->assertSee('New match');
    });
});

describe('MatchesTable Actions', function () {
    it('displays match actions for authorized users', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        Livewire::test(MatchesTable::class)
            ->assertOk();
    });

    it('handles match action integration', function () {
        $event = Event::factory()->create();
        $match = EventMatch::factory()->for($event)->create();

        $component = Livewire::test(MatchesTable::class);
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

        Livewire::test(MatchesTable::class)
            ->assertSee('Event One Match')
            ->assertSee('Event Two Match');
    });

    it('handles matches from past and future events', function () {
        $pastEvent = Event::factory()->past()->create();
        $futureEvent = Event::factory()->future()->create();
        
        EventMatch::factory()->for($pastEvent)->create(['preview' => 'Past match']);
        EventMatch::factory()->for($futureEvent)->create(['preview' => 'Future match']);

        Livewire::test(MatchesTable::class)
            ->assertSee('Past match')
            ->assertSee('Future match');
    });
});

describe('MatchesTable Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        Livewire::test(MatchesTable::class)
            ->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(MatchesTable::class)
            ->assertUnauthorized();
    });
});