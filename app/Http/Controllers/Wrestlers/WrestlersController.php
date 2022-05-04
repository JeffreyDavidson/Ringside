<?php

namespace App\Http\Controllers\Wrestlers;

use App\Data\WrestlerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use App\Services\WrestlerService;

class WrestlersController extends Controller
{
    private WrestlerService $wrestlerService;

    /**
     * Create a new wrestlers controller instance.
     *
     * @param  \App\Services\WrestlerService $wrestlerService
     */
    public function __construct(WrestlerService $wrestlerService)
    {
        $this->wrestlerService = $wrestlerService;
    }

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
     * @param Wrestler $wrestler
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
     * @param  \App\Data\WrestlerData $wrestlerData
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request, WrestlerData $wrestlerData)
    {
        $this->wrestlerService->create($wrestlerData->fromStoreRequest($request));

        return redirect()->route('wrestlers.index');
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
     * @param  \App\Data\WrestlerData $wrestlerData
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Wrestler $wrestler, WrestlerData $wrestlerData)
    {
        $this->wrestlerService->update($wrestler, $wrestlerData->fromUpdateRequest($request));

        return redirect()->route('wrestlers.index');
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

        $this->wrestlerService->delete($wrestler);

        return redirect()->route('wrestlers.index');
    }
}
