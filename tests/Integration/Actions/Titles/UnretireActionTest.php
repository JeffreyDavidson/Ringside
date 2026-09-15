<?php

declare(strict_types=1);

use App\Actions\Titles\UnretireAction;
use App\Models\Titles\Title;

test('it unretires a retired title', function (): void {
    $title = Title::factory()->retired()->create();

    resolve(UnretireAction::class)->handle($title);

    expect($title->refresh()->currentRetirement()->exists())->toBeFalse();
});
