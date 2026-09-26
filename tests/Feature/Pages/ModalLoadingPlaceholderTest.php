<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Promotions\Promotion;

test('resource pages include an accessible placeholder for modal form loading', function (): void {
    $administrator = administrator();
    $promotion = Promotion::factory()->create();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);

    $this->actingAs($administrator)
        ->get(route('wrestlers.index'))
        ->assertOk()
        ->assertSeeHtml('data-test="modal-loading-placeholder"')
        ->assertSeeHtml('wire:target="openModal"')
        ->assertSee(__('core.loading_form'));
});
