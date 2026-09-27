<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

test('tag-team form groups related fields responsively and keeps managers full-width', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('tag-teams.index'));
    $page->resize(390, 844);

    $page
        ->click('Add Tag Team')
        ->assertSee('Add Tag Team')
        ->assertScript('Array.from(document.querySelectorAll("[data-test$=-grid]"), grid => getComputedStyle(grid).gridTemplateColumns.split(" ").length).every(columns => columns === 1)')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(1440, 1000)
        ->assertScript('Array.from(document.querySelectorAll("[data-test$=-grid]"), grid => getComputedStyle(grid).gridTemplateColumns.split(" ").length).join(",") === "2,2"')
        ->assertScript('document.querySelector("[data-test=tag-team-managers-field]").getBoundingClientRect().width > document.querySelector("[data-test=tag-team-wrestlers-grid]").firstElementChild.getBoundingClientRect().width')
        ->assertNoJavascriptErrors();
});
