<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\StoreEventPosterAction;
use App\Data\Events\EventPublicationDraftData;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Http\Requests\Events\SaveEventPublicationRequest;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Queries\Events\PublicEventSourceQuery;
use App\ViewModels\PublicEventViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventPublicPageController
{
    public function edit(Event $event, PublicEventSourceQuery $query): View
    {
        $source = $query->get($event);
        $publication = EventPublication::query()->where('event_id', $event->id)->with(['publisher', 'resultsPublisher'])->first();
        $draft = $publication->draft ?? ['announcement' => 'scheduled', 'summary' => null, 'notice' => null, 'address' => false, 'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null, 'matches' => [], 'results' => []];
        $needsReview = $publication?->card !== null && ($publication->schedule_fingerprint !== $source['schedule'] || $publication->card_fingerprint !== $query->cardFingerprint($source, $publication->card_source ?? []));
        $needsReview = $needsReview || ($publication?->results_published_at !== null
            && $publication->results_fingerprint !== $query->fingerprint($query->resultSource($source, $publication->results_source ?? [])));

        return view('events.publication', ['event' => $event, 'source' => $source, 'publication' => $publication, 'draft' => $draft, 'needsReview' => $needsReview]);
    }

    public function show(Event $event, PublicEventViewModel $page): Response
    {
        $publication = EventPublication::query()->where('event_id', $event->id)->firstOrFail();

        return response()->view('public-events.show', ['page' => $page->preview($event, $publication)])
            ->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function update(Event $event, SaveEventPublicationRequest $request, SaveEventPublicationAction $save, StoreEventPosterAction $posters): RedirectResponse
    {
        $newPosterKey = null;
        $saved = false;
        try {
            try {
                $draft = $request->draft();
                $upload = $request->file('poster');
                if ($upload instanceof UploadedFile) {
                    $newPosterKey = $posters->handle($upload);
                    $draft['poster'] = ['key' => $newPosterKey, 'alt' => $request->string('poster_alt')->toString(), 'caption' => $request->filled('poster_caption') ? $request->string('poster_caption')->toString() : null];
                }
                $save->handle($event, $request->integer('version'), $request->string('fingerprint')->toString(), EventPublicationDraftData::fromValidated($draft));
                $saved = true;
            } finally {
                // This derivative was created for this request and never adopted by a saved draft.
                if (! $saved && $newPosterKey !== null) {
                    Storage::disk('local')->delete('event-posters/'.$newPosterKey);
                }
            }
        } catch (EventPublicationException|RecordChangedException $exception) {

            return back()->withErrors(['publication' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('events.public-pages.edit', $event)->with('success', __('public-events.saved'));
    }
}
