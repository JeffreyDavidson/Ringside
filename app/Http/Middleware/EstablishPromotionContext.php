<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Starts each request from an empty promotion context, then selects the session's promotion while the user
 * still has an active membership of it, otherwise the promotion of their oldest active membership.
 */
class EstablishPromotionContext
{
    public function __construct(private readonly PromotionContextService $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->context->clear();

        $promotions = $this->context->activePromotionsFor($user);
        $promotion = $promotions->firstWhere('id', $request->session()->get('active_promotion_id'))
            ?? $promotions->first();

        if (! $promotion instanceof Promotion) {
            if ($user->role->isAdministrator()) {
                return $next($request);
            }

            throw new HttpResponseException(response()->view('promotions.no-membership', [
                'invitations' => $this->context->pendingInvitationsFor($user),
            ], 403));
        }

        $request->session()->put('active_promotion_id', $promotion->getKey());
        $this->context->set($promotion);
        $this->context->enforce();

        return $next($request);
    }
}
