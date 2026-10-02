<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EstablishPromotionContext
{
    public function __construct(private readonly PromotionContextService $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->context->forgetMemberships();

        $promotions = $this->context->activePromotionsFor($user);

        if ($promotions->isEmpty()) {
            if ($user->role->isAdministrator()) {
                return $next($request);
            }

            throw new HttpResponseException(response()->view('promotions.no-membership', [], 403));
        }

        $selectedPromotionId = $request->session()->get('active_promotion_id');
        $promotion = $selectedPromotionId === null
            ? $promotions->first()
            : $promotions->firstWhere('id', $selectedPromotionId);

        if (! $promotion instanceof Promotion) {
            abort(403, 'The selected promotion membership is not active.');
        }

        $request->session()->put('active_promotion_id', $promotion->getKey());
        $this->context->set($promotion);
        $this->context->enforce();

        return $next($request);
    }
}
