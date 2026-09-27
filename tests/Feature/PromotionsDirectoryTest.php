<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

it('shows platform administrators the directory loading placeholder', function (): void {
    actingAs(administrator());

    $response = $this->get(route('promotions.index'));

    $response->assertOk()
        ->assertSee(__('core.loading_table'))
        ->assertSeeHtml('data-test="table-loading-placeholder"');
});

it('keeps the platform promotions directory unavailable to regular users', function (): void {
    actingAs(basicUser())
        ->get(route('promotions.index'))
        ->assertForbidden();
});
