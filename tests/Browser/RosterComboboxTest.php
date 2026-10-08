<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\actingAs;

function openRosterMatchForm(Event $event, MatchType $matchType): PendingAwaitablePage
{
    $page = visit(route('events.show', $event));

    $page->assertSee('Add Event Match');
    $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");
    waitForScript($page, 'document.querySelector(\'select[name="form.matchType"]\')?.checkVisibility()');
    $page->select('select[name="form.matchType"]', $matchType->value);
    waitForScript($page, 'document.querySelectorAll("#modal-container [data-roster-combobox]").length > 1');

    return $page;
}

function rosterInput(string $field): string
{
    return "input[data-field=\"{$field}\"]";
}

function rosterOption(string $field, string $name): string
{
    return "[data-roster-combobox=\"{$field}\"] [role=\"option\"]:has-text(\"{$name}\")";
}

function rosterChips(string $field): string
{
    return "[data-roster-combobox=\"{$field}\"] [data-test=\"selected-chips\"]";
}

/** JavaScript that is true once the combobox list is open and shows exactly the given number of options. */
function rosterListShows(string $field, int $count): string
{
    return "document.querySelector('[data-roster-combobox=\"{$field}\"] [role=listbox]').checkVisibility()"
        ." && document.querySelectorAll('[data-roster-combobox=\"{$field}\"] [role=option]').length === {$count}";
}

/** JavaScript that reads the polite status message of the combobox. */
function rosterStatus(string $field): string
{
    return "document.querySelector('[data-roster-combobox=\"{$field}\"] [data-test=\"combobox-status\"]')?.textContent.trim()";
}

/** JavaScript that is true once the combobox lists the given name as a selected chip. */
function rosterChipShown(string $field, string $name): string
{
    return "[...document.querySelectorAll('[data-roster-combobox=\"{$field}\"] [data-test=\"selected-chips\"] li')]"
        .".some(chip => chip.checkVisibility() && chip.textContent.includes('{$name}'))";
}

function rosterWireValue(string $field): string
{
    return "Livewire.all().find(component => component.name === 'matches.modals.form-modal').\$wire.\$get('{$field}')";
}

function rosterModalIsOpen(AwaitableWebpage|PendingAwaitablePage $page): bool
{
    return $page->script('() => document.querySelector("#modal-container").checkVisibility()') === true;
}

describe('roster combobox search box', function (): void {
    test('multiple selection keeps the search box free of placeholder text after Tab and Escape', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Referee::factory()->bookable()->create(['first_name' => 'Rita', 'last_name' => 'Ref']);
        Referee::factory()->bookable()->create(['first_name' => 'Rory', 'last_name' => 'Ref']);
        actingAs(administrator());
        $referees = rosterInput('form.referees');

        $page = openRosterMatchForm($event, MatchType::Singles);

        $page->click($referees);
        waitForScript($page, rosterListShows('form.referees', 2));
        $page->click(rosterOption('form.referees', 'Rita Ref'));
        waitForScript($page, rosterChipShown('form.referees', 'Rita Ref'));
        $page
            ->keys($referees, 'Tab')
            ->assertValue($referees, '')
            ->click($referees)
            ->typeSlowly($referees, 'Ror', 20);
        waitForScript($page, rosterListShows('form.referees', 1));
        $page
            ->assertValue($referees, 'Ror')
            ->keys($referees, 'Enter');
        waitForScript($page, rosterChipShown('form.referees', 'Rory Ref'));
        $page
            ->keys($referees, 'Escape')
            ->assertValue($referees, '')
            ->assertSeeIn(rosterChips('form.referees'), 'Rita Ref')
            ->assertDontSeeIn(rosterChips('form.referees'), '#')
            ->assertNoJavascriptErrors();

        expect(rosterModalIsOpen($page))->toBeTrue();
    });

    test('tabbing between tag team comboboxes keeps every search box usable', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Tabbing Wrestler']);
        TagTeam::factory()->bookable()->create(['name' => 'Tabbing Team']);
        actingAs(administrator());
        $wrestlers = rosterInput('form.competitors.0.wrestlers');
        $tagTeams = rosterInput('form.competitors.0.tag_teams');

        $page = openRosterMatchForm($event, MatchType::TagTeam);

        $page->typeSlowly($wrestlers, 'Tabbing', 20);
        waitForScript($page, rosterListShows('form.competitors.0.wrestlers', 1));
        $page->keys($wrestlers, 'Enter');
        waitForScript($page, rosterChipShown('form.competitors.0.wrestlers', 'Tabbing Wrestler'));
        $page->keys($wrestlers, 'Tab');
        waitForScript($page, 'document.activeElement.getAttribute("aria-label") === "Remove Tabbing Wrestler"');
        $page->keys('[aria-label="Remove Tabbing Wrestler"]', 'Tab');
        waitForScript($page, "document.activeElement === document.querySelector('{$tagTeams}')");
        $page->assertValue($wrestlers, '')->typeSlowly($tagTeams, 'Tabbing', 20);
        waitForScript($page, rosterListShows('form.competitors.0.tag_teams', 1));
        $page->keys($tagTeams, 'Enter');
        waitForScript($page, rosterChipShown('form.competitors.0.tag_teams', 'Tabbing Team'));
        $page
            ->keys($tagTeams, 'Tab')
            ->assertValue($tagTeams, '')
            ->assertSeeIn(rosterChips('form.competitors.0.wrestlers'), 'Tabbing Wrestler')
            ->assertNoJavascriptErrors();
    });

    test('a failed save re-renders the form without breaking the search boxes', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Rerender First']);
        Wrestler::factory()->bookable()->create(['name' => 'Rerender Second']);
        $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'Rerender Team']);
        $referee = Referee::factory()->bookable()->create(['first_name' => 'Rerender', 'last_name' => 'Official']);
        actingAs(administrator());
        $teamA = rosterInput('form.competitors.0.wrestlers');
        $teamB = rosterInput('form.competitors.1.tag_teams');
        $referees = rosterInput('form.referees');

        $page = openRosterMatchForm($event, MatchType::TagTeam);

        $page->typeSlowly($teamA, 'Rerender F', 20);
        waitForScript($page, rosterListShows('form.competitors.0.wrestlers', 1));
        $page->keys($teamA, 'Enter');
        waitForScript($page, rosterChipShown('form.competitors.0.wrestlers', 'Rerender First'));
        $page->keys($teamA, 'Escape')->press('Save');
        waitForScript($page, "document.body.textContent.includes('Add at least 2 wrestlers or a tag team to Team A.')");
        $page
            ->assertSee('Add wrestlers or a tag team to Team B.')
            ->assertDontSee('competitors.0')
            ->assertAttributeContains($teamA, 'aria-describedby', 'form.competitors.0.wrestlers-error')
            ->assertValue($teamA, '')
            ->assertSeeIn(rosterChips('form.competitors.0.wrestlers'), 'Rerender First')
            ->typeSlowly($teamA, 'Rerender S', 20);
        waitForScript($page, rosterListShows('form.competitors.0.wrestlers', 1));
        $page->keys($teamA, 'Enter');
        waitForScript($page, rosterChipShown('form.competitors.0.wrestlers', 'Rerender Second'));
        $page->typeSlowly($teamB, 'Rerender', 20);
        waitForScript($page, rosterListShows('form.competitors.1.tag_teams', 1));
        $page->keys($teamB, 'Enter');
        waitForScript($page, rosterChipShown('form.competitors.1.tag_teams', 'Rerender Team'));
        $page->typeSlowly($referees, 'Rerender', 20);
        waitForScript($page, rosterListShows('form.referees', 1));
        $page->keys($referees, 'Enter');
        waitForScript($page, rosterChipShown('form.referees', 'Rerender Official'));
        $page->press('Save');
        waitForScript($page, '! document.querySelector("#modal-container").checkVisibility()');
        $page->assertNoJavascriptErrors();

        $match = EventMatch::query()->whereBelongsTo($event)->sole();
        expect($match->tagTeams()->pluck('tag_teams.id')->all())->toBe([$tagTeam->id])
            ->and($match->wrestlers()->count())->toBe(2)
            ->and($match->referees()->pluck('referees.id')->all())->toBe([$referee->id]);
    });
});

describe('roster combobox loading state', function (): void {
    test('Enter before the results arrive does not book a stale option', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        foreach (range(1, 21) as $number) {
            Wrestler::factory()->bookable()->create(['name' => sprintf('Abe Filler %02d', $number)]);
        }
        $target = Wrestler::factory()->bookable()->create(['name' => 'Zed Target']);
        actingAs(administrator());
        $field = 'form.competitors.0.wrestlers.0';
        $input = rosterInput($field);

        $page = openRosterMatchForm($event, MatchType::Singles);

        $page->click($input);
        waitForScript($page, rosterListShows($field, 20));
        $page
            ->assertScript(rosterStatus($field), 'Showing the first 20 matches. Keep typing to narrow the list.')
            ->assertSeeIn("[data-roster-combobox=\"{$field}\"] [role=listbox]", 'Showing the first 20 matches.')
            ->typeSlowly($input, "Zed\n", 20)
            ->assertValue($input, 'Zed')
            ->assertScript(rosterWireValue($field).' ?? null', null);
        waitForScript($page, rosterListShows($field, 1));
        $page
            ->assertScript(rosterStatus($field), '1 result')
            ->keys($input, 'Enter')
            ->assertValue($input, 'Zed Target');
        waitForScript($page, rosterWireValue($field)." === {$target->id}");
        $page->typeSlowly($input, 'qqqq', 20);
        waitForScript($page, rosterListShows($field, 0));
        $page
            ->assertScript(rosterStatus($field), 'No bookable matches')
            ->assertNoJavascriptErrors();
    });

    test('a failed search request does not leave the combobox searching forever', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Offline Wrestler']);
        actingAs(administrator());
        $field = 'form.competitors.0.wrestlers.0';
        $input = rosterInput($field);

        $page = openRosterMatchForm($event, MatchType::Singles);

        $page->script(<<<'JS'
            () => {
                window.fetch = (uri, options) => {
                    if (String(options?.body).includes('Offline')) {
                        window.offlineSearchFailed = true;
                    }

                    return Promise.reject(new TypeError('Network down'));
                };
            }
            JS);
        $page->typeSlowly($input, 'Offline', 20);

        waitForScript($page, 'window.offlineSearchFailed === true && '.rosterStatus($field)." === ''");
    });
});

describe('roster combobox keyboard and pointer use', function (): void {
    test('the list opens on click and Escape closes the list before the modal', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Wrestler::factory()->bookable()->create(['name' => 'Escape Artist']);
        actingAs(administrator());
        $field = 'form.competitors.0.wrestlers.0';
        $input = rosterInput($field);

        $page = openRosterMatchForm($event, MatchType::Singles);

        $page->click($input);
        waitForScript($page, rosterListShows($field, 1));
        $page
            ->assertAttributeContains($input, 'aria-describedby', "{$field}-hint")
            ->assertScript("document.getElementById('{$field}-hint').textContent.trim().length > 0")
            ->keys($input, 'Escape');
        waitForScript($page, "! document.querySelector('[data-roster-combobox=\"{$field}\"] [role=listbox]').checkVisibility()");

        expect(rosterModalIsOpen($page))->toBeTrue();

        $page->keys($input, 'Escape');
        waitForScript($page, '! document.querySelector("#modal-container").checkVisibility()');
    });

    test('removing a chip keeps focus inside the combobox', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        Referee::factory()->bookable()->create(['first_name' => 'Rita', 'last_name' => 'Ref']);
        Referee::factory()->bookable()->create(['first_name' => 'Rory', 'last_name' => 'Ref']);
        actingAs(administrator());
        $referees = rosterInput('form.referees');

        $page = openRosterMatchForm($event, MatchType::Singles);

        $page->click($referees);
        waitForScript($page, rosterListShows('form.referees', 2));
        $page
            ->click(rosterOption('form.referees', 'Rita Ref'))
            ->click(rosterOption('form.referees', 'Rory Ref'));
        waitForScript($page, rosterChipShown('form.referees', 'Rory Ref'));
        $page
            ->keys($referees, 'Escape')
            ->assertScript("(() => { const box = document.querySelector('[aria-label=\"Remove Rita Ref\"]').getBoundingClientRect(); return box.width >= 24 && box.height >= 24; })()")
            ->keys('[aria-label="Remove Rita Ref"]', 'Enter');
        waitForScript($page, 'document.activeElement.getAttribute("aria-label") === "Remove Rory Ref"');
        $page->keys('[aria-label="Remove Rory Ref"]', 'Enter');
        waitForScript($page, "document.activeElement === document.querySelector('{$referees}')");

        waitForScript($page, rosterWireValue('form.referees').'.length === 0');

        expect(rosterModalIsOpen($page))->toBeTrue();
    });

    test('long chip names stay inside the modal on a narrow screen', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        $longName = 'Maximilian Bartholomew Supercalifragilisticexpialidociousness Montgomery-Fitzgerald';
        Wrestler::factory()->bookable()->create(['name' => $longName]);
        actingAs(administrator());
        $field = 'form.competitors.0.wrestlers';
        $input = rosterInput($field);

        $page = visit(route('events.show', $event));
        $page->resize(375, 812);
        $page->script("Livewire.dispatch('openModal', { component: 'matches.modals.form-modal', arguments: { eventId: {$event->id} } })");
        waitForScript($page, 'document.querySelector(\'select[name="form.matchType"]\')?.checkVisibility()');
        $page->select('select[name="form.matchType"]', MatchType::BattleRoyal->value);
        waitForScript($page, "document.querySelector('{$input}') !== null");
        $page->typeSlowly($input, 'Maximilian', 20);
        waitForScript($page, rosterListShows($field, 1));
        $page->keys($input, 'Enter');
        waitForScript($page, rosterChipShown($field, 'Maximilian'));
        $page
            ->keys($input, 'Escape')
            ->assertAttribute(rosterChips($field).' li span', 'title', $longName)
            ->assertScript("document.querySelector('".rosterChips($field)." li').getBoundingClientRect().right <= document.querySelector('#modal-container').getBoundingClientRect().right")
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth');
    });

    test('tag team sides have unique accessible names', function (): void {
        $event = Event::factory()->scheduled()->withVenue()->create();
        actingAs(administrator());

        $page = openRosterMatchForm($event, MatchType::TagTeam);

        $page
            ->assertSeeIn('fieldset:has([data-roster-combobox="form.competitors.0.wrestlers"]) legend', 'Team A')
            ->assertSeeIn('fieldset:has([data-roster-combobox="form.competitors.1.wrestlers"]) legend', 'Team B')
            ->assertScript(<<<'JS'
                (() => {
                    const names = [...document.querySelectorAll('#modal-container input[role=combobox]')]
                        .map(input => input.getAttribute('aria-labelledby').split(' ')
                            .map(id => document.getElementById(id).textContent.replace(/\s+/g, ' ').trim()).join(' '));

                    return names.length === 5 && new Set(names).size === names.length && names.includes('Team A Wrestlers');
                })()
                JS);
    });
});

test('typing into the next field right after choosing an option stays in that field', function () {
    // Arrange
    $event = Event::factory()->future()->create();
    Wrestler::factory()->bookable()->create(['name' => 'Focus Opponent']);
    Referee::factory()->bookable()->create(['first_name' => 'Rowdy', 'last_name' => 'Focus']);
    actingAs(administrator());
    $page = openRosterMatchForm($event, MatchType::Singles);

    // Act
    $page->typeSlowly(rosterInput('form.competitors.1.wrestlers.0'), 'Focus Opp', 20)
        ->click(rosterOption('form.competitors.1.wrestlers.0', 'Focus Opponent'))
        ->typeSlowly(rosterInput('form.referees'), 'Rowdy', 20);

    // Assert
    $page->assertValue(rosterInput('form.referees'), 'Rowdy')
        ->assertValue(rosterInput('form.competitors.1.wrestlers.0'), 'Focus Opponent')
        ->assertNoJavascriptErrors();
});
