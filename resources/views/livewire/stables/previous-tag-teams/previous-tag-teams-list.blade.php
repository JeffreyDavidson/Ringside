@aware(['stable'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-slot name="header">
        @include('livewire.stables.previous-tag-teams.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.stables.previous-tag-teams.partials.table')
    </x-card.body>
</x-card>
