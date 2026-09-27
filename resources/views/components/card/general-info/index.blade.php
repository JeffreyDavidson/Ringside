<x-card class="!border-ringside-line !bg-ringside-surface-header !text-ringside-ink">
    <x-card.header class="!border-ringside-line">
        <x-card.title class="!text-ringside-ink">General Info</x-card.title>
    </x-card.header>

    <x-card.body class="pt-3.5 pb-3.5">
        <table class="table-auto">
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </x-card.body>
</x-card>
