<?php

namespace App\Http\Controllers\Venues;

use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\StoreRequest;
use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Venue;
use App\Services\VenueService;

class VenuesController extends Controller
{
    public VenueService $venueService;

    /**
     * Create a new venues controller instance.
     *
     * @param  \App\Services\VenueService $venueService
     */
    public function __construct(VenueService $venueService)
    {
        $this->venueService = $venueService;
    }

    /**
     * View a list of venues.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewList', Venue::class);

        return view('venues.index');
    }

    /**
     * Show the form for creating a venue.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Venue $venue)
    {
        $this->authorize('create', Venue::class);

        return view('venues.create', compact('venue'));
    }

    /**
     * Create a new venue.
     *
     * @param  \App\Http\Requests\Venues\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $this->venueService->create($request->validated());

        return redirect()->route('venues.index');
    }

    /**
     * Show the venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\Http\Response
     */
    public function show(Venue $venue)
    {
        $this->authorize('view', $venue);

        return view('venues.show', compact('venue'));
    }

    /**
     * Show the form for editing a venue.
     *
     * @param  \App\Models\Venue  $venue
     * @return \Illuminate\Http\Response
     */
    public function edit(Venue $venue)
    {
        $this->authorize('update', Venue::class);

        return view('venues.edit', compact('venue'));
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
        $this->venueService->update($venue, $request->validated());

        return redirect()->route('venues.index');
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

        $this->venueService->delete($venue);

        return redirect()->route('venues.index');
    }
}
