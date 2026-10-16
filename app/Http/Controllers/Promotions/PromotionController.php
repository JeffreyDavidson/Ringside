<?php

declare(strict_types=1);

namespace App\Http\Controllers\Promotions;

use App\Models\Promotions\Promotion;
use Illuminate\Contracts\View\View;

class PromotionController
{
    /**
     * Display the platform promotion directory.
     */
    public function index(): View
    {
        return view('promotions.index');
    }

    public function show(Promotion $promotion): View
    {
        return view('promotions.show', [
            'promotion' => $promotion->loadCount('memberships'),
        ]);
    }
}
