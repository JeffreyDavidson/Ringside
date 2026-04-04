@aware(['event'])

<x-card class="border-0 shadow-none mb-6 lg:mb-9">
    <x-card.header class="pt-6">
        <x-card.title>
            <h2>Matches</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        @include('livewire.events.matches.partials.table')
    </x-card.body>
</x-card>
