<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use App\Actions\Referees\CreateAction;
use App\Actions\Referees\DeleteAction;
use App\Actions\Referees\UpdateAction;
use App\Data\RefereeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referees\StoreRequest;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;

class RefereesController extends Controller
{
    /**
     * View a list of referees.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Referee::class);

        return view('referees.index');
    }

    /**
     * Show the form for creating a new referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\View\View
     */
    public function create(Referee $referee)
    {
        $this->authorize('create', Referee::class);

        return view('referees.create', [
            'referee' => $referee,
        ]);
    }

    /**
     * Create a new referee.
     *
     * @param  \App\Http\Requests\Referees\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(RefereeData::fromStoreRequest($request));

        return to_route('referees.index');
    }

    /**
     * Show the profile of a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\View\View
     */
    public function show(Referee $referee)
    {
        $this->authorize('view', $referee);

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }

    /**
     * Show the form for editing a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\View\View
     */
    public function edit(Referee $referee)
    {
        $this->authorize('update', $referee);

        return view('referees.edit', [
            'referee' => $referee,
        ]);
    }

    /**
     * Update a given referee.
     *
     * @param  \App\Http\Requests\Referees\UpdateRequest  $request
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Referee $referee)
    {
        UpdateAction::run($referee, RefereeData::fromUpdateRequest($request));

        return to_route('referees.index');
    }

    /**
     * Delete a referee.
     *
     * @param  \App\Models\Referee  $referee
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Referee $referee)
    {
        $this->authorize('delete', $referee);

        DeleteAction::run($referee);

        return to_route('referees.index');
    }
}
