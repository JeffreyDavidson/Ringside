<?php

declare(strict_types=1);

use App\Actions\Titles\ReinstateAction;
use App\Models\Titles\Title;

test('it reinstates an inactive title', function (): void {
    $title = Title::factory()->inactive()->create();

    resolve(ReinstateAction::class)->handle($title);

    expect($title->refresh()->currentActivityPeriod()->exists())->toBeTrue();
});
