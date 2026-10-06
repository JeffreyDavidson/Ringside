<?php

declare(strict_types=1);

namespace App\Mail\Promotions;

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tells an invited email address that a promotion invited it. The message is identical whether or not an account
 * exists for the address, carries no token, and grants nothing: the invitation is accepted in the application after
 * signing in with the invited email (AcceptPromotionInvitationAction).
 */
final class PromotionInvitationMail extends Mailable
{
    public function __construct(
        public readonly PromotionInvitation $invitation,
        public readonly User $invitedBy,
    ) {}

    public function envelope(): Envelope
    {
        $this->invitation->loadMissing('promotion');

        return new Envelope(
            subject: __('mail.promotion_invitation.subject', ['promotion' => $this->invitation->promotion->name]),
        );
    }

    public function content(): Content
    {
        $this->invitation->loadMissing('promotion');
        $promotion = $this->invitation->promotion;

        return new Content(
            markdown: 'mail.promotions.invitation',
            with: [
                'inviter' => $this->invitedBy->full_name,
                'promotion' => $promotion->name,
                'role' => $this->invitation->role->label(),
                'expires' => Promotion::toLocalTime($promotion, $this->invitation->expires_at)->format('M j, Y'),
                'loginUrl' => route('login'),
                'registerUrl' => route('register'),
            ],
        );
    }
}
