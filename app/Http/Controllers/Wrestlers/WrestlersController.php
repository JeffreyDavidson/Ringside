<?php

declare(strict_types=1);

namespace App\Http\Controllers\Wrestlers;

use App\Actions\Wrestlers\CreateAction;
use App\Actions\Wrestlers\DeleteAction;
use App\Actions\Wrestlers\UpdateAction;
use App\Data\WrestlerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WrestlersController extends Controller
{
    /**
     * View a list of employed wrestlers.
     */
    public function index(): View
    {
        Gate::authorize('viewList', Wrestler::class);

        return view('wrestlers.index');
    }

    /**
     * Show the form for creating a new wrestler.
     */
    public function create(Wrestler $wrestler): View
    {
        Gate::authorize('create', Wrestler::class);

        return view('wrestlers.create', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Create a new wrestler.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(WrestlerData::fromStoreRequest($request));

        return to_route('wrestlers.index');
    }

    /**
     * Show the profile of a wrestler.
     */
    public function show(Wrestler $wrestler): View
    {
        Gate::authorize('view', $wrestler);

        return view('wrestlers.show', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Show the form for editing a wrestler.
     */
    public function edit(Wrestler $wrestler): View
    {
        Gate::authorize('update', $wrestler);

        return view('wrestlers.edit', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Update a given wrestler.
     */
    public function update(UpdateRequest $request, Wrestler $wrestler): RedirectResponse
    {
        UpdateAction::run($wrestler, WrestlerData::fromUpdateRequest($request));

        return to_route('wrestlers.index');
    }

    /**
     * Delete a wrestler.
     */
    public function destroy(Wrestler $wrestler): RedirectResponse
    {
        Gate::authorize('delete', $wrestler);

        DeleteAction::run($wrestler);

        return to_route('wrestlers.index');
    }
}
