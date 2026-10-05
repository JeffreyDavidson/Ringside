{{-- Every promotion page sends a user without an active membership back here, so it offers no promotion navigation. --}}
<x-errors.page
    :title="__('promotions.no_membership_title')"
    :description="__('promotions.no_membership_description')"
    :show-dashboard-link="false"
>
    <x-promotions.invitations :invitations="$invitations" />
</x-errors.page>
