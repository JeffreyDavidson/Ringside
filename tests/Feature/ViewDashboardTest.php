<?php

test('administrators can view the dashboard', function () {
    $this->actingAs(administrator())
        ->get(route('dashboard'))
        ->assertViewIs('dashboard');
});

test('basic users can view the dashboard', function () {
    $this->actingAs(basicUser())
        ->get(route('dashboard'))
        ->assertViewIs('dashboard');
});

test('a guest cannot view the dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
