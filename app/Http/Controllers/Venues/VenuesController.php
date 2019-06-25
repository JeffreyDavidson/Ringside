<?php

namespace App\Http\Controllers\Venues;

use App\Models\Venue;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use Illuminate\Database\Eloquent\Builder;

class VenuesController extends Controller
{
    /**
     * Retrieve all venues.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, DataTables $table)
    {
        $this->authorize('viewList', Venue::class);

        if ($request->ajax()) {
            $query = Venue::query();

            return $table->eloquent($query)
                ->filterColumn('address', function (Builder $query, $keyword) {
                    $query->whereRaw('CONCAT(venues.address1, venues.address2)  LIKE ?', ["%{$keyword}%"]);
                })
                ->addColumn('action', 'venues.partials.action-cell')
                ->toJson();
        }

        return view('venues.index');
    }

    /**
     * Show the form for creating a venue.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Venue::class);

        return view('venues.create');
    }

    /**
     * Create a new venue.
     *
     * @param  \App\Http\Requests\StoreVenueRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreVenueRequest $request)
    {
        Venue::create($request->all());

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
     * Update a given Venue.
     *
     * @param  \App\Http\Requests\UpdateVenueRequest  $request
     * @param  \App\Models\Venue  $venue
     * @return \lluminate\Http\RedirectResponse
     */
    public function update(UpdateVenueRequest $request, Venue $venue)
    {
        $venue->update($request->all());

        return redirect()->route('venues.index');
    }
}
