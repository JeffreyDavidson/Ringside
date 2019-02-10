<?php

namespace App\Http\Controllers;

use App\Wrestler;
use App\Http\Requests\StoreWrestlerRequest;

class WrestlersController extends Controller
{
    /**
     * Show the form for creating a new wrestler.
     *
     * @return Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Wrestler::class);

        return response()->view('wrestlers.create');
    }

    /**
     * Create a new wrestler.
     *
     * @param  \App\Http\Requests\StoreWrestlerRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreWrestlerRequest $request)
    {
        $request->merge(['height' => ($request->input('feet') * 12) + $request->input('inches')]);

        $wrestler = Wrestler::create($request->except(['feet', 'inches']));

        return redirect('/wrestlers');
    }
}
