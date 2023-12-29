@aware(['tagTeam'])

<x-card>
    <x-slot name="header">
        @include('livewire.tag-teams.managers.partials.header')
    </x-slot>

    <x-card.body class="pt-0">
        @include('livewire.tag-teams.managers.partials.table')
    </x-card.body>
</x-card>
