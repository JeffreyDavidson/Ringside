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
use App\Services\Events\PublicEventSourceService;
use App\Services\Events\PublishedEventChangesService;
use App\ViewModels\Events\PublicEventViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EventPublicPageController
{
    public function edit(Event $event, PublicEventSourceService $sources, PublishedEventChangesService $changes): View
    {
        $source = $sources->get($event);
        $publication = EventPublication::query()->forEventId($event->id)->with(['publisher', 'resultsPublisher'])->first();
        $draft = $publication->draft ?? ['announcement' => 'scheduled', 'summary' => null, 'notice' => null, 'address' => false, 'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null, 'matches' => [], 'results' => []];
        $needsReview = false;

        if ($publication !== null) {
            $changed = $changes->compare($publication, $source);
            $needsReview = ($publication->card !== null && ($changed['scheduleChanged'] || $changed['cardChanged']))
                || ($publication->results_published_at !== null && $changed['resultsChanged']);
        }

        return view('events.publication', ['event' => $event, 'source' => $source, 'publication' => $publication, 'draft' => $draft, 'needsReview' => $needsReview]);
    }

    public function show(Event $event, PublicEventViewModel $page): Response
    {
        $publication = EventPublication::query()->forEventId($event->id)->firstOrFail();

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
