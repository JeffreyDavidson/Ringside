<?php

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

test('it contains the referees list component', function () {
    $this->actingAs(administrator())
        ->view('referees.index')
        ->assertSeeLivewire('referees.referees-list');
});
