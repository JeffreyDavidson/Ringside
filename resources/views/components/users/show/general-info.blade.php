<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.name')" :value="$user->full_name" />
    <x-card.general-info.stat :label="__('core.general_info.email')" :value="$user->email" />
    @if ($user->phone_number)
        <x-card.general-info.stat :label="__('core.general_info.phone')" :value="$user->phone_number->formatted()" />
    @endif
    <x-card.general-info.stat :label="__('core.general_info.role')" :value="$user->role->label()" />
    <x-card.general-info.stat :label="__('core.general_info.account_status')" :value="$user->status->label()" />
    <x-card.general-info.stat
        :label="__('core.general_info.email_verification')"
        :value="$user->email_verified_at ? __('core.general_info.verified') : __('core.general_info.not_verified')"
    />
</x-card.general-info>
