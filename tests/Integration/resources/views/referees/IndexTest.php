<?php

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

test('it contains the managers list component', function () {
    $this->actingAs(administrator())
        ->view('managers.index')
        ->assertSeeLivewire('managers.managers-list');
});
