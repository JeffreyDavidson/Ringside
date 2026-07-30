<?php

declare(strict_types=1);

test('basic example', function () {
    $page = visit(route('login'));

    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});
