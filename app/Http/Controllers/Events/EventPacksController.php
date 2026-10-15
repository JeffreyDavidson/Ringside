<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Actions\Events\SaveEventPackAction;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Http\Requests\Events\UpdateEventPackRequest;
use App\Models\Events\Event;
use App\ViewModels\EventPackViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventPacksController
{
    public function show(Event $event, Request $request, EventPackViewModel $viewModel): View
    {
        return view($request->boolean('print') ? 'events.pack-print' : 'events.pack', $viewModel->data($event));
    }

    public function update(Event $event, UpdateEventPackRequest $request, SaveEventPackAction $save): RedirectResponse
    {
        try {
            $save->handle($event, $request->integer('version'), $request->string('fingerprint')->toString(), $request->content(), $request->boolean('reviewed'));
        } catch (EventPackConflictException|RecordChangedException $exception) {
            return back()->withErrors(['pack' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('events.pack.show', ['event' => $event, ...($request->boolean('print_after') ? ['print' => 1] : [])])
            ->with('success', __('event-pack.saved'));
    }
}
