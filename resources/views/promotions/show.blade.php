<x-layouts.show-page :title="$promotion->name">
    <x-slot:sidebar>
        <x-card.general-info>
            <x-card.general-info.stat label="Slug" :value="$promotion->slug" />
            <x-card.general-info.stat label="Members" :value="$promotion->memberships_count" />
            <x-card.general-info.stat label="Created" :value="$promotion->created_at?->toFormattedDateString()" />
        </x-card.general-info>
    </x-slot:sidebar>

    <livewire:promotions.members.manage :promotion-id="$promotion->id" />
</x-layouts.show-page>
