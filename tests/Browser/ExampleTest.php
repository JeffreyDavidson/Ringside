<?php

declare(strict_types=1);

test('basic example', function () {
    $page = visit('/');

    $page->assertSee('Laravel')
        ->assertNoJavascriptErrors();
});
