<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Exceptions\Events\EventPublicationException;
use App\Exceptions\RecordChangedException;
use App\Http\Requests\Events\ReleaseEventPublicationRequest;
use App\Models\Events\Event;
use Illuminate\Http\RedirectResponse;

class ReleaseEventPublicationController
{
    public function __invoke(Event $event, ReleaseEventPublicationRequest $request, ReleaseEventPublicationAction $release): RedirectResponse
    {
        try {
            $release->handle($event, $request->user() ?? abort(403), $request->integer('version'), $request->string('fingerprint')->toString(), PublicSection::from($request->string('section')->toString()));
        } catch (EventPublicationException|RecordChangedException $exception) {
            return back()->withErrors(['publication' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('events.public-pages.edit', $event)->with('success', __('public-events.published'));
    }
}
