<x-card>
    <x-card.header>
        <x-card.title>General Info</x-card.title>
    </x-card.header>

    <x-card.body class="pt-3.5 pb-3.5">
        <table class="table-auto">
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </x-card.body>
</x-card>
