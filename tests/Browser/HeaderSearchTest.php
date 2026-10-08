<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;
use Pest\Browser\Api\PendingAwaitablePage;

use function Pest\Laravel\actingAs;

const HEADER_SEARCH_TOGGLE = 'button[aria-label="Search navigation"]';
const HEADER_SEARCH_LINKS = '() => [...document.querySelectorAll("[data-test=header-search-results] a")].filter((link) => link.checkVisibility()).map((link) => link.dataset.section)';

/**
 * Wait until the header search reports itself open or closed and its transition has finished.
 */
function waitForHeaderSearch(PendingAwaitablePage $page, bool $open): void
{
    $expanded = $open ? 'true' : 'false';

    waitForSettledScript($page, 'document.querySelector(\''.HEADER_SEARCH_TOGGLE.'\').getAttribute("aria-expanded") === "'.$expanded.'"');
}

beforeEach(function (): void {
    actingAs(administrator());
});

test('the keyboard shortcut opens the search with focus in the field', function (string $shortcut): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    $page->resize(1440, 900);

    // Act
    $page->keys('[data-test=app-shell-header]', $shortcut);
    waitForHeaderSearch($page, true);

    // Assert
    $page->assertAttribute(HEADER_SEARCH_TOGGLE, 'aria-expanded', 'true')
        ->assertScript('document.activeElement === document.getElementById("shell-search")')
        ->assertScript('getComputedStyle(document.querySelector("[data-test=header-search-field]")).outlineStyle !== "none"')
        ->assertScript(HEADER_SEARCH_LINKS, [
            'Overview', 'Events', 'Wrestlers', 'Tag teams', 'Managers', 'Referees', 'Stables', 'Titles', 'Venues', 'Promotions', 'User management',
        ])
        ->assertNoJavascriptErrors()
        ->assertNoAccessibilityIssues();
})->with([
    'control' => ['Control+k'],
    'command' => ['Meta+k'],
]);

test('typing filters the sections and escape returns focus to the toggle', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    $page->resize(1440, 900);
    $page->click(HEADER_SEARCH_TOGGLE);
    waitForHeaderSearch($page, true);

    // Act
    $page->typeSlowly('#shell-search', 'ven');

    // Assert
    $page->assertScript(HEADER_SEARCH_LINKS, ['Events', 'Venues'])
        ->typeSlowly('#shell-search', 'zzz')
        ->assertScript(HEADER_SEARCH_LINKS, [])
        ->assertSee('No sections match your search.')
        ->keys('#shell-search', 'Escape');
    waitForHeaderSearch($page, false);
    $page->assertAttribute(HEADER_SEARCH_TOGGLE, 'aria-expanded', 'false')
        ->assertScript('document.activeElement === document.querySelector(\''.HEADER_SEARCH_TOGGLE.'\')')
        ->assertNoJavascriptErrors();
});

test('enter opens the first matching section', function (): void {
    // Arrange
    $page = visit(route('wrestlers.index'));
    $page->resize(1440, 900);
    $page->click(HEADER_SEARCH_TOGGLE);
    waitForHeaderSearch($page, true);

    // Act
    $page->typeSlowly('#shell-search', 'venue')
        ->keys('#shell-search', 'Enter');

    // Assert
    $page->assertPathIs('/venues')
        ->assertNoJavascriptErrors();
});

test('members only see the sections they can open', function (): void {
    // Arrange
    $member = basicUser();
    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($member, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    actingAs($member);
    $page = visit(route('dashboard'));
    $page->resize(1440, 900);

    // Act
    $page->click(HEADER_SEARCH_TOGGLE);
    waitForHeaderSearch($page, true);

    // Assert
    $page->assertScript(HEADER_SEARCH_LINKS, [
        'Overview', 'Events', 'Wrestlers', 'Tag teams', 'Managers', 'Referees', 'Stables', 'Titles', 'Venues',
    ])->assertNoJavascriptErrors();
});
