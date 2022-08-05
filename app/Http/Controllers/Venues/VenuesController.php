<?php

declare(strict_types=1);

namespace App\Http\Controllers\Venues;

use App\Actions\Venues\CreateAction;
use App\Actions\Venues\DeleteAction;
use App\Actions\Venues\UpdateAction;
use App\Data\VenueData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Venue;

class VenuesController extends Controller
{
    /**
     * View a list of venues.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('viewList', Venue::class);

        return view('venues.index');
    }

    /**
     * Show the form for creating a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\View\View
     */
    public function create(Venue $venue)
    {
        $this->authorize('create', Venue::class);

        return view('venues.create', [
            'venue' => $venue,
        ]);
    }

    /**
     * Create a new venue.
     *
     * @param  \App\Http\Requests\Venues\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        CreateAction::run(VenueData::fromStoreRequest($request));

        return to_route('venues.index');
    }

    /**
     * Show the venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\View\View
     */
    public function show(Venue $venue)
    {
        $this->authorize('view', $venue);

        return view('venues.show', [
            'venue' => $venue,
        ]);
    }

    /**
     * Show the form for editing a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\View\View
     */
    public function edit(Venue $venue)
    {
        $this->authorize('update', Venue::class);

        return view('venues.edit', [
            'venue' => $venue,
        ]);
    }

    /**
     * Update a given venue.
     *
     * @param  \App\Http\Requests\Venues\UpdateRequest  $request
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Venue $venue)
    {
        UpdateAction::run($venue, VenueData::fromUpdateRequest($request));

        return to_route('venues.index');
    }

    /**
     * Delete a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Venue $venue)
    {
        $this->authorize('delete', $venue);

        DeleteAction::run($venue);

        return to_route('venues.index');
    }
}
