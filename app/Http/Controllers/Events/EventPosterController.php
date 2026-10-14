<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Queries\Events\PublishedEventQuery;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventPosterController
{
    public function __invoke(string $publicId, string $slug, string $asset, PublishedEventQuery $query): StreamedResponse
    {
        $publication = $query->find($publicId, $slug);
        $page = $query->page($publication);
        abort_if($page->posterUrl === null || $publication->card === null || $publication->card['poster'] === null || ! hash_equals($publication->card['poster']['key'], $asset), 404);

        return $this->image($publication->card['poster']['key']);
    }

    private function image(string $key): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists('event-posters/'.$key), 404);

        return Storage::disk('local')->response('event-posters/'.$key, null, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
