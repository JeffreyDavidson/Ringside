<?php

use App\Models\Title;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

test('it contains the titles championship list component', function () {
    $title = Title::factory()->create();

    $this->actingAs(administrator())
        ->view('titles.show', [
            'title' => $title,
        ])
        ->assertSeeLivewire('titles.title-championships-list');
});
