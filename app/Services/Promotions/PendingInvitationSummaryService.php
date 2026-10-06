<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Models\Promotions\PromotionInvitation;
use Illuminate\Support\Collection;

/**
 * Describes the pending promotion invitations behind email addresses, so an administrator can see what an
 * account would be able to accept before activating it or moving it onto another address.
 */
class PendingInvitationSummaryService
{
    /**
     * One query for any number of emails.
     *
     * @param  array<int, string>  $emails
     * @return array<string, string> Normalized email => "Acme Wrestling (Owner), Beta Pro (Member)", only for emails with pending invitations.
     */
    public function forEmails(array $emails): array
    {
        return PromotionInvitation::query()
            ->forEmails($emails)
            ->pending()
            ->with('promotion')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('email')
            ->map(fn (Collection $invitations): string => $invitations
                ->map(fn (PromotionInvitation $invitation): string => "{$invitation->promotion->name} ({$invitation->role->label()})")
                ->implode(', '))
            ->all();
    }

    /** The summary for a single email, or null when it has no pending invitations. */
    public function forEmail(string $email): ?string
    {
        return $this->forEmails([$email])[PromotionInvitation::normalizeEmail($email)] ?? null;
    }
}
