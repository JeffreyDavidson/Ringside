<?php

declare(strict_types=1);

namespace App\Http\Controllers\Referees;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
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
     */
    public function index(): View
    {
        $this->authorize('viewList', Referee::class);

        return view('referees.index');
    }

    /**
     * Show the form for creating a new referee.
     */
    public function create(Referee $referee): View
    {
        $this->authorize('create', Referee::class);

        return view('referees.create', [
            'referee' => $referee,
        ]);
    }

    /**
     * Create a new referee.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(RefereeData::fromStoreRequest($request));

        return to_route('referees.index');
    }

    /**
     * Show the profile of a referee.
     */
    public function show(Referee $referee): View
    {
        $this->authorize('view', $referee);

        return view('referees.show', [
            'referee' => $referee,
        ]);
    }

    /**
     * Show the form for editing a referee.
     */
    public function edit(Referee $referee): View
    {
        $this->authorize('update', $referee);

        return view('referees.edit', [
            'referee' => $referee,
        ]);
    }

    /**
     * Update a given referee.
     */
    public function update(UpdateRequest $request, Referee $referee): RedirectResponse
    {
        UpdateAction::run($referee, RefereeData::fromUpdateRequest($request));

        return to_route('referees.index');
    }

    /**
     * Delete a referee.
     */
    public function destroy(Referee $referee): RedirectResponse
    {
        $this->authorize('delete', $referee);

        DeleteAction::run($referee);

        return to_route('referees.index');
    }
}
