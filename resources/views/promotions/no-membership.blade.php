{{-- Every promotion page sends a user without an active membership back here, so it offers no promotion navigation. --}}
<x-errors.page
    :title="__('promotions.no_membership_title')"
    :description="__('promotions.no_membership_description')"
    :show-dashboard-link="false"
>
    @if (is_string(session('status')) && session('status') !== '')
        <p
            role="status"
            data-test="no-membership-status"
            class="bg-ringside-surface-panel text-ringside-ink border-ringside-success border border-s-4 px-4 py-3 text-sm"
        >
            {{ session('status') }}
        </p>
    @endif

    @if (is_string(session('error')) && session('error') !== '')
        <p
            role="alert"
            data-test="no-membership-error"
            class="bg-ringside-surface-panel text-ringside-ink border-ringside-signal-soft border border-s-4 px-4 py-3 text-sm"
        >
            {{ session('error') }}
        </p>
    @endif

    <x-promotions.invitations :invitations="$invitations" />
</x-errors.page>
