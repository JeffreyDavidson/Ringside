<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

test('wrestler form fields stack on narrow screens and use responsive rows on wider screens', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('wrestlers.create'));
    $page->resize(390, 844);

    $page
        ->assertSee('Add Wrestler')
        ->assertScript('document.querySelectorAll("[data-test^=wrestler-]").length === 3')
        ->assertScript('Array.from(document.querySelectorAll("[data-test^=wrestler-]"), grid => getComputedStyle(grid).gridTemplateColumns.split(" ").length).every(columns => columns === 1)')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(1440, 1000)
        ->assertScript('Array.from(document.querySelectorAll("[data-test^=wrestler-]"), grid => getComputedStyle(grid).gridTemplateColumns.split(" ").length).join(",") === "2,3,2"')
        ->assertNoJavascriptErrors();
});
