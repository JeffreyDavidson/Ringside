<x-card>
    <x-slot name="header">
        @include('livewire.venues.events.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.venues.events.partials.table')
    </x-card.body>
</x-card>
