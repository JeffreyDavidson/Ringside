<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Queries\Events\PublishedEventQuery;
use App\ViewModels\PublicEventViewModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicEventController
{
    public function __invoke(string $publicId, string $slug, Request $request, PublishedEventQuery $query, PublicEventViewModel $page): Response
    {
        $publication = $query->get($publicId, $slug) ?? abort(404);
        $card = $publication->card ?? abort(404);
        $results = $request->routeIs('shows.results');
        abort_if($results && $publication->results_published_at === null, 404);
        $data = $page->page($publication, $card, $results);

        return response()->view('public-events.show', ['page' => $data])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', $data->isResults ? 'noindex, nofollow' : 'index, follow');
    }
}
