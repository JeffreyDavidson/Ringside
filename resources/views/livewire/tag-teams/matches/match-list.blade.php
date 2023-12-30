@aware(['tagTeam'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-slot name="header">
        @include('livewire.tag-teams.matches.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.tag-teams.matches.partials.table')
    </x-card.body>
</x-card>
