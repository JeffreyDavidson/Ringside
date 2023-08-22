<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

test('it contains the wrestlers list component', function () {
    $this->actingAs(administrator())
        ->view('wrestlers.index')
        ->assertSeeLivewire('wrestlers.wrestlers-list');
});
