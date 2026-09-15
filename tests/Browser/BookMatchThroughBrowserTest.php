<?php

declare(strict_types=1);

use App\Models\Events\Event;

test('administrator can access match booking from the event page', function (): void {
    $event = Event::factory()->scheduled()->withVenue()->create();

    $this->actingAs(administrator());

    $page = visit(route('events.show', $event));

    $page->assertSee('Add Event Match')->assertNoJavascriptErrors();
});
