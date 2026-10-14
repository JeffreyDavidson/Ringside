<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Queries\Events\PublishedEventQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicEventController
{
    public function __invoke(string $publicId, string $slug, Request $request, PublishedEventQuery $query): Response
    {
        $data = $query->page($query->find($publicId, $slug), $request->routeIs('shows.results'));

        return response()->view('public-events.show', ['page' => $data])->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', $data->isResults ? 'noindex, nofollow' : 'index, follow');
    }
}
