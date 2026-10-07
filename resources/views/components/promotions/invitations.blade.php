@props(['invitations'])

{{-- The signed-in user's own pending invitations. Accepting or declining posts to routes outside the promotion context. --}}
@if ($invitations->isNotEmpty())
    <section aria-labelledby="promotion-invitations-heading" data-test="pending-invitations" {{ $attributes }}>
        <h2 id="promotion-invitations-heading" class="text-ringside-ink text-sm font-semibold">
            {{ __('promotions.invitations_pending') }}
        </h2>
        @if (auth()->user()?->role->isAdministrator())
            <p class="text-ringside-muted mt-1 text-xs" data-test="invitation-admin-note">
                {{ __('promotions.invitation_administrator_note') }}
            </p>
        @endif
        <ul class="divide-ringside-line mt-3 divide-y">
            @foreach ($invitations as $invitation)
                <li class="flex flex-col gap-3 py-3">
                    <div class="min-w-0">
                        <p class="text-ringside-ink truncate text-sm font-semibold">
                            {{ $invitation->promotion->name }}
                        </p>
                        <p class="text-ringside-muted mt-1 text-xs">
                            {{ __('promotions.invitation_role', ['role' => $invitation->role->label()]) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form
                            method="post"
                            action="{{ route('promotions.invitation.accept', $invitation->promotion_id) }}"
                        >
                            @csrf
                            <x-button
                                type="submit"
                                variant="ringside"
                                size="sm"
                            >{{ __('promotions.accept') }}</x-button>
                        </form>
                        <form
                            method="post"
                            action="{{ route('promotions.invitation.decline', $invitation->promotion_id) }}"
                        >
                            @csrf
                            <x-button
                                type="submit"
                                variant="secondary"
                                size="sm"
                            >{{ __('promotions.decline') }}</x-button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
