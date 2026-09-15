<?php

declare(strict_types=1);

use App\Models\Events\Event;

test('administrator can access the event list in a real browser', function (): void {
    $event = Event::factory()->scheduled()->create([
        'name' => 'Browser Smoke Event',
    ]);

    $this->actingAs(administrator());

    $page = visit(route('events.index'));

    $page->assertSee('Events')
        ->assertSee($event->name)
        ->assertNoJavascriptErrors();
});
