<x-card.general-info>
    <x-card.general-info.stat label="Name" :value="$user->full_name" />
    <x-card.general-info.stat label="Email" :value="$user->email" />
    @if ($user->phone_number)
        <x-card.general-info.stat label="Phone" :value="$user->phone_number->formatted()" />
    @endif
    <x-card.general-info.stat label="Role" :value="$user->role->label()" />
    <x-card.general-info.stat label="Account Status" :value="$user->status->label()" />
    <x-card.general-info.stat
        label="Email Verification"
        :value="$user->email_verified_at ? 'Verified' : 'Not verified'"
    />
</x-card.general-info>
