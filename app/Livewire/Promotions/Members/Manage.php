<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Members;

use App\Actions\Promotions\InvitePromotionMemberAction;
use App\Actions\Promotions\RemovePromotionInvitationAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Promotions\PromotionInvitationOutcome;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Models\Promotions\Promotion;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Manage extends Component
{
    use DispatchesActionFeedback;
    use WithPagination;

    #[Locked]
    public int $promotionId;

    private const int MAX_INVITATIONS_PER_HOUR = 30;

    private const int MEMBERS_PER_PAGE = 25;

    public string $email = '';

    public string $newMemberRole = MembershipRole::Member->value;

    /** @var array<int, string> */
    public array $memberRoles = [];

    public function mount(int $promotionId): void
    {
        $this->promotionId = $promotionId;

        Gate::authorize('view', $this->promotion());
    }

    public function addMember(): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $this->resetErrorBag('email');

        $validated = Validator::make(
            ['email' => mb_trim($this->email), 'role' => $this->newMemberRole],
            [
                'email' => ['required', 'string', 'email', 'max:255'],
                'role' => ['required', Rule::enum(MembershipRole::class)],
            ],
        )->validate();

        $role = MembershipRole::from($validated['role']);

        $rateLimitKey = "promotion-invitations:{$this->promotionId}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_INVITATIONS_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($rateLimitKey) / 60);

            $this->addError('email', trans_choice('promotions.invitation_rate_limited', $minutes, ['minutes' => $minutes]));

            return;
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $outcome = app(InvitePromotionMemberAction::class)->handle($promotion, $validated['email'], $role);

        // The owner already sees every member and pending invitation of the promotion, so these two cases
        // reveal nothing new. Every other email gets the same message whether or not it belongs to an account.
        $error = match ($outcome) {
            PromotionInvitationOutcome::Invited => null,
            PromotionInvitationOutcome::AlreadyInvited => __('promotions.invitation_already_pending'),
            PromotionInvitationOutcome::AlreadyMember => __('promotions.member_already_added'),
        };

        if ($error !== null) {
            $this->addError('email', $error);

            return;
        }

        $this->reset('email');
        $this->dispatchActionSuccess(__('promotions.invitation_sent', ['role' => $role->label()]));
    }

    public function cancelInvitation(int $invitationId): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $invitation = $promotion->invitations()
            ->whereKey($invitationId)
            ->firstOrFail();

        app(RemovePromotionInvitationAction::class)->handle($promotion, $invitation->email);

        $this->resetErrorBag(['email', 'member']);
        $this->dispatchActionSuccess(__('promotions.invitation_cancelled'));
    }

    public function updateMemberRole(int $userId): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $user = $this->memberUser($promotion, $userId);
        Validator::make(
            ['role' => $this->memberRoles[$userId] ?? null],
            ['role' => ['required', Rule::enum(MembershipRole::class)]],
        )->validate();

        $role = MembershipRole::from($this->memberRoles[$userId]);

        try {
            app(UpdatePromotionMemberRoleAction::class)->handle(
                $promotion,
                $user,
                $role,
            );
        } catch (BaseBusinessException $exception) {
            $this->memberRoles[$userId] = $this->currentRole($promotion, $userId);
            $this->addError('member', $exception->getMessage());

            return;
        }

        $this->resetErrorBag(['email', 'member']);
        $this->dispatchActionSuccess(__('promotions.member_role_updated', ['name' => $user->full_name, 'role' => $role->label()]));
    }

    public function updateMemberStatus(int $userId, string $status): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $user = $this->memberUser($promotion, $userId);
        Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::in([
                MembershipStatus::Active->value,
                MembershipStatus::Suspended->value,
            ])]],
        )->validate();

        $membershipStatus = MembershipStatus::from($status);

        try {
            app(UpdatePromotionMemberStatusAction::class)->handle(
                $promotion,
                $user,
                $membershipStatus,
            );
        } catch (BaseBusinessException $exception) {
            $this->addError('member', $exception->getMessage());

            return;
        }

        $this->resetErrorBag(['email', 'member']);
        $this->dispatchActionSuccess(__(
            $membershipStatus === MembershipStatus::Suspended
                ? 'promotions.member_suspended'
                : 'promotions.member_reactivated',
            ['name' => $user->full_name],
        ));
    }

    public function render(): View
    {
        $promotion = $this->promotion();
        Gate::authorize('view', $promotion);

        $members = $promotion->memberships()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('user_id')
            ->paginate(self::MEMBERS_PER_PAGE);

        // Members on the page being shown get a role entry; unsaved edits stay untouched.
        foreach ($members as $member) {
            $this->memberRoles[$member->user_id] ??= $member->role->value;
        }

        $canManageMembers = Gate::allows('manageMembers', $promotion);

        $invitations = $canManageMembers
            ? $promotion->invitations()->pending()->orderBy('created_at')->orderBy('id')->get()
            : new Collection;

        return view('livewire.promotions.members.manage', [
            'members' => $members,
            'promotion' => $promotion,
            'invitations' => $invitations,
            'canManageMembers' => $canManageMembers,
            'roleOptions' => collect(MembershipRole::cases())->mapWithKeys(fn (MembershipRole $role): array => [$role->value => $role->label()])->all(),
            'activeStatus' => MembershipStatus::Active,
            'suspendedStatus' => MembershipStatus::Suspended,
        ]);
    }

    /** @param array<string, mixed> $params */
    public function placeholder(array $params = []): View
    {
        return view('livewire.promotions.members.loading-placeholder');
    }

    private function promotion(): Promotion
    {
        return Promotion::query()->findOrFail($this->promotionId);
    }

    private function currentRole(Promotion $promotion, int $userId): string
    {
        return $promotion->memberships()
            ->where('user_id', $userId)
            ->firstOrFail()
            ->role
            ->value;
    }

    private function memberUser(Promotion $promotion, int $userId): User
    {
        return User::query()
            ->whereHas('promotionMemberships', function (Builder $query) use ($promotion): void {
                $query->where('promotion_id', $promotion->getKey());
            })
            ->whereKey($userId)
            ->firstOrFail();
    }
}
