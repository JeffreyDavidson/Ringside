<?php

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

test('it contains the titles list component', function () {
    $this->actingAs(administrator())
        ->view('titles.index')
        ->assertSeeLivewire('titles.titles-list');
});
