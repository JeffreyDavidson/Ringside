<x-card>
    <x-slot name="header">
        @include('livewire.title-championships.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.title-championships.partials.table')
    </x-card.body>
</x-card>
