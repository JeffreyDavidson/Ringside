<?php

use App\Models\MatchDecision;

test('a match decision has a name', function () {
    $matchDecision = MatchDecision::factory()->create(['name' => 'Example Match Decision Name']);

    expect($matchDecision)->name->toBe('Example Match Decision Name');
});

test('a match decision has a slug', function () {
    $matchDecision = MatchDecision::factory()->create(['slug' => 'example-slug']);

    expect($matchDecision)->slug->toBe('example-slug');
});
