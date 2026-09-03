<?php

declare(strict_types=1);

test('the login page is available in a real browser', function (): void {
    $page = visit(route('login'));

    $page->assertSee('Sign in')
        ->assertNoJavascriptErrors();
});
