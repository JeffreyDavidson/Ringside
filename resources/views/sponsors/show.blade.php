<x-layouts.app :title="$sponsor->name">
    <livewire:sponsors.components.directory :promotionId="$sponsor->promotion_id" :sponsorId="$sponsor->id" />
</x-layouts.app>
