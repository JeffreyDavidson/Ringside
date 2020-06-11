<?php

namespace App\Http\Controllers\Wrestlers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wrestlers\StoreRequest;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;
use App\ViewModels\WrestlerViewModel;

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
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', Wrestler::class);

        return view('wrestlers.create', new WrestlerViewModel());
    }

    /**
     * Create a new wrestler.
     *
     * @param  App\Http\Requests\Wrestlers\StoreRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRequest $request)
    {
        $wrestler = Wrestler::create($request->except('started_at'));

        if ($request->filled('started_at')) {
            $wrestler->employ($request->input('started_at'));
        }

        return redirect()->route('wrestlers.index');
    }

    /**
     * Show the profile of a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function show(Wrestler $wrestler)
    {
        $this->authorize('view', $wrestler);

        return view('wrestlers.show', compact('wrestler'));
    }

    /**
     * Show the form for editing a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return \Illuminate\View\View
     */
    public function edit(Wrestler $wrestler)
    {
        $this->authorize('update', $wrestler);

        return view('wrestlers.edit', new WrestlerViewModel($wrestler));
    }

    /**
     * Update a given wrestler.
     *
     * @param  App\Http\Requests\Wrestlers\UpdateRequest  $request
     * @param  App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRequest $request, Wrestler $wrestler)
    {
        $wrestler->update($request->except('started_at'));

        if ($request->filled('started_at')) {
            $wrestler->employ($request->input('started_at'));
        }

        return redirect()->route('wrestlers.index');
    }

    /**
     * Delete a wrestler.
     *
     * @param  App\Models\Wrestler  $wrestler
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Wrestler $wrestler)
    {
        $this->authorize('delete', $wrestler);

        $wrestler->delete();

        return redirect()->route('wrestlers.index');
    }
}
