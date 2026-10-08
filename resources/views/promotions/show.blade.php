<x-layouts.show-page :title="$promotion->name">
    <x-slot:sidebar>
        <x-card.general-info>
            <x-card.general-info.stat :label="__('core.general_info.slug')" :value="$promotion->slug" />
            <x-card.general-info.stat :label="__('core.general_info.members')" :value="$promotion->memberships_count" />
            <x-card.general-info.stat
                :label="__('core.general_info.created')"
                :value="$promotion->created_at?->toFormattedDateString()"
            />
        </x-card.general-info>
    </x-slot:sidebar>

    <livewire:promotions.members.manage :promotion-id="$promotion->id" defer.bundle />
</x-layouts.show-page>
