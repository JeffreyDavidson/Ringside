@aware(['wrestler'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-slot name="header">
        @include('livewire.wrestlers.previous-managers.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.wrestlers.previous-managers.partials.table')
    </x-card.body>
</x-card>
