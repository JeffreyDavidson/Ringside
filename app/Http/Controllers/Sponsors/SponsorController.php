<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sponsors;

use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Contracts\View\View;

class SponsorController
{
    public function index(PromotionContextService $context): View
    {
        abort_unless($context->current() instanceof Promotion, 403);

        return view('sponsors.index', ['promotionId' => $context->required()->id]);
    }

    public function show(Sponsor $sponsor): View
    {
        return view('sponsors.show', ['sponsor' => $sponsor]);
    }
}
