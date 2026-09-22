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
    public function __invoke(): View
    {
        return view('promotions.index', [
            'promotions' => Promotion::query()
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
