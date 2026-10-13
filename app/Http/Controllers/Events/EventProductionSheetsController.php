<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Requests\Events\EventProductionSheetRequest;
use App\Models\Events\Event;
use App\ViewModels\EventProductionViewModel;
use Illuminate\Contracts\View\View;

class EventProductionSheetsController
{
    public function show(Event $event, EventProductionSheetRequest $request, EventProductionViewModel $viewModel): View
    {
        return view($request->boolean('print') ? 'events.production-print' : 'events.production', [
            'sheet' => $viewModel->data($event, $request->filled('source') ? $request->string('source')->toString() : null,
                $request->filled('person') ? $request->integer('person') : null, $request->filled('role') ? $request->string('role')->toString() : null),
        ]);
    }
}
