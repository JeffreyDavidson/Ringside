@props(['matches'])

<x-card class="card-flush mb-6 mb-xl-9">
    <x-slot name="header">
        <x-matches.partials.header />
    </x-slot>

    <x-card.body class="pt-0">
        @foreach($matches as $match)
            <x-matches.match :match="$match" :loop="$loop" />
        @endforeach
    </x-card.body>
</x-card>
