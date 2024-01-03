<x-card>
    <x-slot name="header">
        @include('livewire.venues.previous-events.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.venues.previous-events.partials.table')
    </x-card.body>
</x-card>
