<?php

declare(strict_types=1);

namespace App\Builders\Promotions;

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of PromotionInvitation
 *
 * @extends Builder<TModel>
 */
class PromotionInvitationBuilder extends Builder
{
    public function forPromotion(Promotion $promotion): static
    {
        return $this->whereBelongsTo($promotion, 'promotion');
    }

    /** Invitations are stored normalized, so the email is normalized the same way before the exact comparison. */
    public function forEmail(string $email): static
    {
        return $this->where('email', PromotionInvitation::normalizeEmail($email));
    }

    /**
     * @param  array<int, string>  $emails
     */
    public function forEmails(array $emails): static
    {
        return $this->whereIn('email', array_values(array_unique(array_map(PromotionInvitation::normalizeEmail(...), $emails))));
    }

    /** Invitations that can still be accepted: the expiry moment itself counts as expired. */
    public function pending(): static
    {
        return $this->where('expires_at', '>', now());
    }

    public function oldestFirst(): static
    {
        return $this->orderBy('created_at')->orderBy('id');
    }

    public function expired(): static
    {
        return $this->where('expires_at', '<=', now());
    }
}
