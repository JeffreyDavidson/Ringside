<?php

declare(strict_types=1);

namespace App\Livewire\Promotions\Members;

use App\Actions\Promotions\InvitePromotionMemberAction;
use App\Actions\Promotions\RemovePromotionInvitationAction;
use App\Actions\Promotions\UpdatePromotionMemberRoleAction;
use App\Actions\Promotions\UpdatePromotionMemberStatusAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Concerns\DispatchesActionFeedback;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionMembership;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Manage extends Component
{
    use DispatchesActionFeedback;

    #[Locked]
    public int $promotionId;

    public string $email = '';

    public string $newMemberRole = MembershipRole::Member->value;

    /** @var array<int, string> */
    public array $memberRoles = [];

    public function mount(int $promotionId): void
    {
        $this->promotionId = $promotionId;

        Gate::authorize('view', $this->promotion());

        $this->memberRoles = PromotionMembership::query()
            ->where('promotion_id', $this->promotionId)
            ->joined()
            ->get(['user_id', 'role'])
            ->mapWithKeys(fn (PromotionMembership $membership): array => [
                $membership->user_id => $membership->role->value,
            ])
            ->all();
    }

    public function addMember(): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $this->resetErrorBag('email');

        $validated = Validator::make(
            ['email' => $this->email, 'role' => $this->newMemberRole],
            [
                'email' => ['required', 'string', 'max:255'],
                'role' => ['required', Rule::enum(MembershipRole::class)],
            ],
        )->validate();

        $email = mb_strtolower(trim($validated['email']));

        // whereLike only narrows the candidates (case-insensitively on every engine); the exact comparison is done in PHP so
        // `%` and `_` in the input can never widen the match.
        $user = User::query()
            ->whereLike('email', $email, caseSensitive: false)
            ->where('status', UserStatus::Active)
            ->get()
            ->first(fn (User $candidate): bool => mb_strtolower($candidate->email) === $email);

        $role = MembershipRole::from($validated['role']);

        // The owner already sees every member of the promotion, so naming this case reveals nothing new.
        if ($user instanceof User && ! app(InvitePromotionMemberAction::class)->handle($promotion, $user, $role)) {
            $this->addError('email', __('promotions.member_already_added'));

            return;
        }

        // Unknown, inactive and invited emails share one message, and it never names the account, so the
        // message cannot be used to discover accounts. (The pending row that appears in the list for a real
        // account is the one remaining signal; see "Promotion member invitations" in the refactoring backlog.)
        $this->reset('email');
        $this->dispatchActionSuccess(__('promotions.invitation_sent', ['role' => $role->label()]));
    }

    public function cancelInvitation(int $userId): void
    {
        $promotion = $this->promotion();
        Gate::authorize('manageMembers', $promotion);

        $invitee = User::query()
            ->whereHas('promotionMemberships', function (Builder $query) use ($promotion): void {
                $query->where('promotion_id', $promotion->getKey())
                    ->where('status', MembershipStatus::Invited);
            })
            ->whereKey($userId)
            ->firstOrFail();

        app(RemovePromotionInvitationAction::class)->handle($promotion, $invitee);

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

        $memberships = PromotionMembership::query()
            ->where('promotion_id', $promotion->getKey())
            ->with('user')
            ->orderBy('created_at')
            ->get();

        [$invitations, $members] = $memberships->partition(
            fn (PromotionMembership $membership): bool => $membership->status === MembershipStatus::Invited,
        );

        $canManageMembers = Gate::allows('manageMembers', $promotion);

        return view('livewire.promotions.members.manage', [
            'members' => $members,
            'invitations' => $invitations,
            'canManageMembers' => $canManageMembers,
            'roles' => MembershipRole::cases(),
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
                $query->where('promotion_id', $promotion->getKey())
                    ->where('status', '!=', MembershipStatus::Invited);
            })
            ->whereKey($userId)
            ->firstOrFail();
    }
}
