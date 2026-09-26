<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

test('resource index pages render a table loading placeholder in the initial response', function (): void {
    $administrator = administrator();
    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    $this->actingAs($administrator);

    foreach ([
        'events.index',
        'managers.index',
        'promotions.index',
        'referees.index',
        'stables.index',
        'tag-teams.index',
        'titles.index',
        'users.index',
        'venues.index',
        'wrestlers.index',
    ] as $routeName) {
        $response = $this->get(route($routeName));

        $response->assertOk()
            ->assertSeeHtml('data-test="table-loading-placeholder"')
            ->assertSeeHtml('aria-busy="true"')
            ->assertSee(__('core.loading_table'));
    }
});
