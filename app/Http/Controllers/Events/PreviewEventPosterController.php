<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreviewEventPosterController
{
    public function __invoke(Event $event): StreamedResponse
    {
        $publication = EventPublication::query()->forEventId($event->id)->firstOrFail();
        $poster = $publication->draft['poster'];
        abort_if($poster === null, 404);
        abort_unless(Storage::disk('local')->exists('event-posters/'.$poster['key']), 404);

        return Storage::disk('local')->response('event-posters/'.$poster['key'], null, [
            'Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
