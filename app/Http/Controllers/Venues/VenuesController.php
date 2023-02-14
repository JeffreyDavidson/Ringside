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
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VenuesController extends Controller
{
    /**
     * View a list of venues.
     */
    public function index(): View
    {
        $this->authorize('viewList', Venue::class);

        return view('venues.index');
    }

    /**
     * Show the form for creating a venue.
     */
    public function create(Venue $venue): View
    {
        $this->authorize('create', Venue::class);

        return view('venues.create', [
            'venue' => $venue,
        ]);
    }

    /**
     * Create a new venue.
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        CreateAction::run(VenueData::fromStoreRequest($request));

        return to_route('venues.index');
    }

    /**
     * Show the venue.
     */
    public function show(Venue $venue): View
    {
        $this->authorize('view', $venue);

        return view('venues.show', [
            'venue' => $venue,
        ]);
    }

    /**
     * Show the form for editing a venue.
     */
    public function edit(Venue $venue): View
    {
        $this->authorize('update', Venue::class);

        return view('venues.edit', [
            'venue' => $venue,
        ]);
    }

    /**
     * Update a given venue.
     */
    public function update(UpdateRequest $request, Venue $venue): RedirectResponse
    {
        UpdateAction::run($venue, VenueData::fromUpdateRequest($request));

        return to_route('venues.index');
    }

    /**
     * Delete a venue.
     */
    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        DeleteAction::run($venue);

        return to_route('venues.index');
    }
}
