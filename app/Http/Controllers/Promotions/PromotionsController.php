<?php

declare(strict_types=1);

namespace App\Http\Controllers\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\Promotion;
use Illuminate\Contracts\View\View;

class PromotionsController extends Controller
{
    /**
     * Display the platform promotion directory.
     */
    public function index(): View
    {
        return view('promotions.index', [
            'promotions' => Promotion::query()
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Promotion $promotion): View
    {
        return view('promotions.show', [
            'promotion' => $promotion->loadCount('memberships'),
        ]);
    }
}
