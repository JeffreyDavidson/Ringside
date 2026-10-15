<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('browser tab titles', function (): void {
    it('escapes page names in the browser tab', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()
            ->create(['name' => "Shane O'Neil & Co"]);

        actingAs(administrator());

        // Act
        $response = get(route('wrestlers.show', $wrestler));

        // Assert
        $response
            ->assertOk()
            ->assertSeeHtml('<title>Shane O&#039;Neil &amp; Co · Ringside</title>');
    });
});
