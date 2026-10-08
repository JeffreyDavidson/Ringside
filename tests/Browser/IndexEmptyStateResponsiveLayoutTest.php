<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

use function Pest\Laravel\actingAs;

test('empty states remain within the viewport across resource indexes on phones', function (): void {
    $administrator = administrator();
    actingAs($administrator);

    $promotionDirectory = visit(route('promotions.index'));

    foreach ([320, 390] as $viewportWidth) {
        $promotionDirectory->resize($viewportWidth, 844);
        $promotionDirectory->assertVisible('[data-test="promotions-empty-state"]');
        $promotionDirectory->assertScript('getComputedStyle(document.querySelector("[data-test=promotions-empty-state]")).paddingInlineStart === "16px"');
        $promotionDirectory->assertScript('document.documentElement.scrollWidth <= innerWidth');
    }

    $promotionDirectory->resize(768, 1024);
    $promotionDirectory->assertScript('getComputedStyle(document.querySelector("[data-test=promotions-empty-state]")).paddingInlineStart === "24px"');
    $promotionDirectory->assertScript('document.documentElement.scrollWidth <= innerWidth');
    $promotionDirectory->assertNoJavascriptErrors();

    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    $emptyIndexes = [
        'events.index' => 'events-empty-state',
        'managers.index' => 'managers-empty-state',
        'referees.index' => 'referees-empty-state',
        'stables.index' => 'stables-empty-state',
        'tag-teams.index' => 'tag-team-empty-state',
        'titles.index' => 'titles-empty-state',
        'venues.index' => 'venues-empty-state',
        'wrestlers.index' => 'roster-empty-state',
    ];

    foreach ($emptyIndexes as $routeName => $testId) {
        $page = visit(route($routeName));
        $selector = '[data-test='.$testId.']';

        foreach ([320, 390] as $viewportWidth) {
            $page->resize($viewportWidth, 844);
            $page->assertVisible($selector);
            $page->assertScript('(() => { const state = Array.from(document.querySelectorAll("[data-test]")).find((element) => element.dataset.test === "'.$testId.'"); const rect = state.getBoundingClientRect(); return rect.left >= 0 && rect.right <= innerWidth && Array.from(state.querySelectorAll("h2, p, button")).every((element) => { const child = element.getBoundingClientRect(); return child.left >= 0 && child.right <= innerWidth && element.scrollWidth <= element.clientWidth + 1; }); })()');
            $page->assertScript('getComputedStyle(document.querySelector("'.$selector.'")).paddingInlineStart === "16px"');
            $page->assertScript('document.documentElement.scrollWidth <= innerWidth');
        }

        $page->resize(768, 1024);
        $page->assertScript('getComputedStyle(document.querySelector("'.$selector.'")).paddingInlineStart === "24px"');
        $page->assertScript('document.documentElement.scrollWidth <= innerWidth');

        $page->assertNoJavascriptErrors();
    }

    foreach ([
        'promotions.index' => ['promotions-empty-state', '#promotions-search', 'No matching mobile promotion'],
        'users.index' => ['users-empty-state', '#users-search', 'No matching mobile user'],
    ] as $routeName => [$testId, $searchSelector, $searchTerm]) {
        $page = visit(route($routeName));
        $page->resize(320, 844);
        $page->fill($searchSelector, $searchTerm);
        $page->assertVisible('[data-test="'.$testId.'"]');

        foreach ([320, 390] as $viewportWidth) {
            $page->resize($viewportWidth, 844);
            $page->assertScript('(() => { const state = Array.from(document.querySelectorAll("[data-test]")).find((element) => element.dataset.test === "'.$testId.'"); const rect = state.getBoundingClientRect(); return rect.left >= 0 && rect.right <= innerWidth && Array.from(state.querySelectorAll("h2, p, button")).every((element) => { const child = element.getBoundingClientRect(); return child.left >= 0 && child.right <= innerWidth && element.scrollWidth <= element.clientWidth + 1; }); })()');
            $page->assertScript('getComputedStyle(document.querySelector("[data-test='.$testId.']")).paddingInlineStart === "16px"');
            $page->assertScript('document.documentElement.scrollWidth <= innerWidth');
        }

        $page->resize(768, 1024);
        $page->assertScript('getComputedStyle(document.querySelector("[data-test='.$testId.']")).paddingInlineStart === "24px"');
        $page->assertScript('document.documentElement.scrollWidth <= innerWidth');

        $page->assertNoJavascriptErrors();
    }
});
