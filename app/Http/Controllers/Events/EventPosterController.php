<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Queries\Events\PublishedEventChangesQuery;
use App\Queries\Events\PublishedEventQuery;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventPosterController
{
    public function __invoke(string $publicId, string $slug, string $asset, PublishedEventQuery $query, PublishedEventChangesQuery $changes): StreamedResponse
    {
        $publication = $query->get($publicId, $slug) ?? abort(404);
        $card = $publication->card ?? abort(404);
        // A poster whose event schedule changed after release is no longer shown.
        abort_if($card['poster'] === null || ! hash_equals($card['poster']['key'], $asset) || $changes->get($publication)['scheduleChanged'], 404);

        return $this->image($card['poster']['key']);
    }

    private function image(string $key): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists('event-posters/'.$key), 404);

        return Storage::disk('local')->response('event-posters/'.$key, null, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
