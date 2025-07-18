<?php

declare(strict_types=1);

use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Modals\FormModal;
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

describe('FormModal Configuration', function () {
    it('returns correct form class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getFormClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(CreateEditForm::class);
    });

    it('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        expect($method->invoke($modal))->toBe(EventMatch::class);
    });
});

describe('FormModal Rendering', function () {
    it('can render in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertOk();
    });

    it('can render in edit mode', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $match->id);

        $component->assertOk();
    });

    it('displays correct title in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('Create Match');
    });

    it('displays correct title in edit mode', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $match->id);

        $component->assertSee('Edit Match');
    });

    it('presents wrestlers list for selection', function () {
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Test Wrestler']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('Test Wrestler');
    });

    it('presents referees list for selection', function () {
        $referee = Referee::factory()->bookable()->create(['name' => 'Test Referee']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('Test Referee');
    });

    it('presents match types list for selection', function () {
        $matchType = MatchType::factory()->create(['name' => 'Singles Match']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('Singles Match');
    });
});

describe('FormModal Create Operations', function () {
    it('can create a new match with valid data', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();
        $referee = Referee::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->set('form.referees', [$referee->id])
            ->set('form.preview', 'Epic wrestling match preview')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');

        $this->assertDatabaseHas('event_matches', [
            'event_id' => $this->event->id,
            'match_type_id' => $matchType->id,
            'preview' => 'Epic wrestling match preview',
        ]);
    });

    it('validates required fields when creating', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', '')
            ->set('form.match_type_id', '')
            ->set('form.competitors', [])
            ->call('save');

        $component->assertHasErrors([
            'form.event_id' => 'required',
            'form.match_type_id' => 'required',
            'form.competitors' => 'required',
        ]);
    });

    it('validates minimum number of competitors', function () {
        $matchType = MatchType::factory()->create();
        $wrestler = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler->id])
            ->call('save');

        $component->assertHasErrors(['form.competitors']);
    });

    it('validates event exists', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', 999)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->call('save');

        $component->assertHasErrors(['form.event_id']);
    });

    it('validates match type exists', function () {
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', 999)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->call('save');

        $component->assertHasErrors(['form.match_type_id']);
    });

    it('validates competitors exist and are bookable', function () {
        $matchType = MatchType::factory()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [999, 998])
            ->call('save');

        $component->assertHasErrors(['form.competitors']);
    });

    it('validates referees exist and are bookable', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->set('form.referees', [999])
            ->call('save');

        $component->assertHasErrors(['form.referees']);
    });
});

describe('FormModal Edit Operations', function () {
    it('can edit an existing match', function () {
        $match = EventMatch::factory()->for($this->event)->create();
        $newMatchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $match->id)
            ->set('form.match_type_id', $newMatchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->set('form.preview', 'Updated match preview')
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchUpdated');

        $this->assertDatabaseHas('event_matches', [
            'id' => $match->id,
            'match_type_id' => $newMatchType->id,
            'preview' => 'Updated match preview',
        ]);
    });

    it('loads existing match data in edit mode', function () {
        $matchType = MatchType::factory()->create();
        $match = EventMatch::factory()
            ->for($this->event)
            ->for($matchType)
            ->create(['preview' => 'Original preview']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $match->id);

        $component->assertSet('form.event_id', $this->event->id);
        $component->assertSet('form.match_type_id', $matchType->id);
        $component->assertSet('form.preview', 'Original preview');
    });
});

describe('FormModal Title Championship Integration', function () {
    it('can create championship match with title stakes', function () {
        $title = Title::factory()->active()->create();
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->set('form.titles', [$title->id])
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');

        $match = EventMatch::where('event_id', $this->event->id)->first();
        expect($match->titles)->toContain($title);
    });

    it('presents available titles for championship matches', function () {
        $title = Title::factory()->active()->create(['name' => 'World Championship']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('World Championship');
    });

    it('validates title stakes are active titles', function () {
        $inactiveTitle = Title::factory()->inactive()->create();
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->set('form.titles', [$inactiveTitle->id])
            ->call('save');

        $component->assertHasErrors(['form.titles']);
    });
});

describe('FormModal Tag Team Integration', function () {
    it('can create tag team match', function () {
        $matchType = MatchType::factory()->create();
        $tagTeam1 = TagTeam::factory()->bookable()->create();
        $tagTeam2 = TagTeam::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$tagTeam1->id, $tagTeam2->id])
            ->call('save');

        $component->assertHasNoErrors();
        $component->assertDispatched('matchCreated');
    });

    it('presents available tag teams for selection', function () {
        $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'The Hardy Boyz']);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertSee('The Hardy Boyz');
    });
});

describe('FormModal State Management', function () {
    it('resets form when switching modes', function () {
        $match = EventMatch::factory()->for($this->event)->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal', $match->id)
            ->call('openModal');

        $component->assertSet('form.event_id', null);
        $component->assertSet('form.match_type_id', null);
        $component->assertSet('form.competitors', []);
    });

    it('closes modal after successful save', function () {
        $matchType = MatchType::factory()->create();
        $wrestler1 = Wrestler::factory()->bookable()->create();
        $wrestler2 = Wrestler::factory()->bookable()->create();

        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', $this->event->id)
            ->set('form.match_type_id', $matchType->id)
            ->set('form.competitors', [$wrestler1->id, $wrestler2->id])
            ->call('save');

        $component->assertDispatched('closeModal');
    });

    it('keeps modal open when validation fails', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.event_id', '')
            ->call('save');

        $component->assertNotDispatched('closeModal');
    });
});

describe('FormModal Authorization', function () {
    it('requires authentication', function () {
        auth()->logout();

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertUnauthorized();
    });

    it('requires administrator privileges', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(FormModal::class)
            ->call('openModal');

        $component->assertUnauthorized();
    });
});