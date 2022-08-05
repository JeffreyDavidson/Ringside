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

class WrestlersController extends Controller
{
    /**
     * View a list of employed wrestlers.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Wrestler::class);

        return view('wrestlers.index');
    }

    /**
     * Show the form for creating a new wrestler.
     *
     * @param  Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function create(Wrestler $wrestler)
    {
        $this->authorize('create', Wrestler::class);

        return view('wrestlers.create', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Create a new wrestler.
     *
     * @param  \App\Http\Requests\Wrestlers\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(WrestlerData::fromStoreRequest($request));

        return to_route('wrestlers.index');
    }

    /**
     * Show the profile of a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function show(Wrestler $wrestler)
    {
        $this->authorize('view', $wrestler);

        return view('wrestlers.show', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Show the form for editing a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function edit(Wrestler $wrestler)
    {
        $this->authorize('update', $wrestler);

        return view('wrestlers.edit', [
            'wrestler' => $wrestler,
        ]);
    }

    /**
     * Update a given wrestler.
     *
     * @param  \App\Http\Requests\Wrestlers\UpdateRequest  $request
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Wrestler $wrestler)
    {
        UpdateAction::run($wrestler, WrestlerData::fromUpdateRequest($request));

        return to_route('wrestlers.index');
    }

    /**
     * Delete a wrestler.
     *
     * @param  \App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('delete', $wrestler);

        DeleteAction::run($wrestler);

        return to_route('wrestlers.index');
    }
}
