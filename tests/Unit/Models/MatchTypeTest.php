<?php

use App\Models\MatchType;

test('a match type has a name', function () {
    $matchType = MatchType::factory()->create(['name' => 'Example Match Type Name']);

    expect($matchType)->name->toBe('Example Match Type Name');
});

test('a match type has a slug', function () {
    $matchType = MatchType::factory()->create(['slug' => 'example-slug']);

    expect($matchType)->slug->toBe('example-slug');
});

test('a match type has a defined number of sides to the match', function () {
    $matchType = MatchType::factory()->create(['number_of_sides' => 2]);

    expect($matchType)->number_of_sides->toBe(2);
});
