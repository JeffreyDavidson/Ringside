<?php

declare(strict_types=1);

use App\Livewire\Matches\Forms\CreateEditForm;
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
    $this->event = Event::factory()->create();
});

describe('Form Validation Rules', function () {
    it('validates required fields', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', '')
            ->set('match_type_id', '')
            ->set('competitors', [])
            ->call('store');

        $form->assertHasErrors([
            'event_id' => 'required',
            'match_type_id' => 'required',
            'competitors' => 'required',
        ]);
    });

    it('validates event exists', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', 999)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasErrors(['event_id' => 'exists']);
    });

    it('validates match type exists', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', 999)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasErrors(['match_type_id' => 'exists']);
    });

    it('validates competitors array', function () {
        $matchType = MatchType::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['competitors' => 'array']);
    });

    it('validates minimum number of competitors', function () {
        $matchType = MatchType::factory()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler->id])
            ->call('store');

        $form->assertHasErrors(['competitors' => 'min']);
    });

    it('validates maximum number of competitors', function () {
        $matchType = MatchType::factory()->create();
        $wrestlers = Wrestler::factory()->bookable()->count(10)->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', $wrestlers->pluck('id')->toArray())
            ->call('store');

        $form->assertHasErrors(['competitors' => 'max']);
    });

    it('accepts valid number of competitors', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Competitor Validation', function () {
    it('validates competitors exist', function () {
        $matchType = MatchType::factory()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [999, 998])
            ->call('store');

        $form->assertHasErrors(['competitors.0' => 'exists']);
        $form->assertHasErrors(['competitors.1' => 'exists']);
    });

    it('validates competitors are bookable', function () {
        $matchType = MatchType::factory()->create();
        $unbookableWrestler = Wrestler::factory()->unemployed()->create();
        $bookableWrestler = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$unbookableWrestler->id, $bookableWrestler->id])
            ->call('store');

        $form->assertHasErrors(['competitors.0']);
        $form->assertHasNoErrors(['competitors.1']);
    });

    it('validates competitors are unique', function () {
        $matchType = MatchType::factory()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler->id, $wrestler->id])
            ->call('store');

        $form->assertHasErrors(['competitors' => 'distinct']);
    });

    it('accepts valid competitor combinations', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id, $tagTeam->id])
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Referee Validation', function () {
    it('validates referees array', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['referees' => 'array']);
    });

    it('validates referees exist', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [999])
            ->call('store');

        $form->assertHasErrors(['referees.0' => 'exists']);
    });

    it('validates referees are bookable', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $unbookableReferee = Referee::factory()->unemployed()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [$unbookableReferee->id])
            ->call('store');

        $form->assertHasErrors(['referees.0']);
    });

    it('accepts valid referee assignments', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [$referee->id])
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Title Validation', function () {
    it('validates titles array', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', 'invalid-array')
            ->call('store');

        $form->assertHasErrors(['titles' => 'array']);
    });

    it('validates titles exist', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', [999])
            ->call('store');

        $form->assertHasErrors(['titles.0' => 'exists']);
    });

    it('validates titles are active', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $inactiveTitle = Title::factory()->inactive()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', [$inactiveTitle->id])
            ->call('store');

        $form->assertHasErrors(['titles.0']);
    });

    it('accepts valid title stakes', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $title = Title::factory()->active()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', [$title->id])
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Field Validation', function () {
    it('validates preview maximum length', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $longPreview = str_repeat('a', 1001);

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('preview', $longPreview)
            ->call('store');

        $form->assertHasErrors(['preview' => 'max']);
    });

    it('accepts valid preview length', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $validPreview = str_repeat('a', 500);

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('preview', $validPreview)
            ->call('store');

        $form->assertHasNoErrors();
    });
});

describe('Form Store Operations', function () {
    it('can store valid match data', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();
        $title = Title::factory()->active()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [$referee->id])
            ->set('titles', [$title->id])
            ->set('preview', 'Epic wrestling match preview')
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertDispatched('matchCreated');

        $this->assertDatabaseHas('event_matches', [
            'event_id' => $this->event->id,
            'match_type_id' => $matchType->id,
            'preview' => 'Epic wrestling match preview',
        ]);
    });

    it('stores match with minimal required data', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasNoErrors();

        $this->assertDatabaseHas('event_matches', [
            'event_id' => $this->event->id,
            'match_type_id' => $matchType->id,
        ]);
    });

    it('creates competitor relationships', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasNoErrors();

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->competitors)->toContain($wrestler1);
        expect($match->competitors)->toContain($wrestler2);
    });

    it('creates referee relationships', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [$referee->id])
            ->call('store');

        $form->assertHasNoErrors();

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->referees)->toContain($referee);
    });

    it('creates title relationships', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $title = Title::factory()->active()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', [$title->id])
            ->call('store');

        $form->assertHasNoErrors();

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->titles)->toContain($title);
    });
});

describe('Form Update Operations', function () {
    it('can update existing match', function () {
        $match = EventMatch::factory()->for($this->event)->create();
        $newMatchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $match)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $newMatchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('preview', 'Updated match preview')
            ->call('update');

        $form->assertHasNoErrors();
        $form->assertDispatched('matchUpdated');

        $this->assertDatabaseHas('event_matches', [
            'id' => $match->id,
            'match_type_id' => $newMatchType->id,
            'preview' => 'Updated match preview',
        ]);
    });

    it('can update match relationships', function () {
        $match = EventMatch::factory()->for($this->event)->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $match)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $match->match_type_id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('referees', [$referee->id])
            ->call('update');

        $form->assertHasNoErrors();

        $match->refresh();
        expect($match->competitors)->toContain($wrestler1);
        expect($match->competitors)->toContain($wrestler2);
        expect($match->referees)->toContain($referee);
    });
});

describe('Form State Management', function () {
    it('resets form after successful store', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->call('store');

        $form->assertHasNoErrors();
        $form->assertSet('event_id', null);
        $form->assertSet('match_type_id', null);
        $form->assertSet('competitors', []);
    });

    it('preserves form state when validation fails', function () {
        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', 999)
            ->set('competitors', [])
            ->call('store');

        $form->assertHasErrors();
        $form->assertSet('event_id', $this->event->id);
        $form->assertSet('match_type_id', 999);
        $form->assertSet('competitors', []);
    });

    it('loads existing model data correctly', function () {
        $matchType = MatchType::factory()->create();
        $match = EventMatch::factory()
            ->for($this->event)
            ->for($matchType)
            ->create(['preview' => 'Test preview']);

        $form = Livewire::test(CreateEditForm::class)
            ->call('setModel', $match);

        $form->assertSet('event_id', $this->event->id);
        $form->assertSet('match_type_id', $matchType->id);
        $form->assertSet('preview', 'Test preview');
    });
});

describe('Form Business Logic', function () {
    it('validates mixed competitor types', function () {
        $matchType = MatchType::factory()->create();
        $wrestler = Wrestler::factory()->bookable()->create();
        $tagTeam = TagTeam::factory()->bookable()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler->id, $tagTeam->id])
            ->call('store');

        $form->assertHasNoErrors();

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->competitors)->toContain($wrestler);
        expect($match->competitors)->toContain($tagTeam);
    });

    it('validates championship implications', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $title = Title::factory()->active()->create();

        $form = Livewire::test(CreateEditForm::class)
            ->set('event_id', $this->event->id)
            ->set('match_type_id', $matchType->id)
            ->set('competitors', [$wrestler1->id, $wrestler2->id])
            ->set('titles', [$title->id])
            ->call('store');

        $form->assertHasNoErrors();

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->titles)->toContain($title);
        expect($match->isChampionshipMatch())->toBeTrue();
    });
});