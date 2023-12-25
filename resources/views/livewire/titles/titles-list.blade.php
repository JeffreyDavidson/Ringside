<x-card>
    <x-slot name="header">
        @include('livewire.titles.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.titles.partials.table')
    </x-card.body>
</x-card>
