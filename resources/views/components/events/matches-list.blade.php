@props(['matches'])

<x-card class="mb-6 border-0 shadow-none lg:mb-9">
    <x-card.header class="mt-6">
        <x-card.title>
            <h2>Matches</h2>
        </x-card.title>
    </x-card.header>

    <x-card.body class="pt-0">
        @foreach ($matches as $match)
            <x-matches.match :match="$match" :loop="$loop" />
        @endforeach
    </x-card.body>
</x-card>
