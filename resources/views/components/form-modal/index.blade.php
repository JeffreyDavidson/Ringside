<x-modal>
    <div class="flex flex-col space-y-4">
        {{ $slot }}
    </div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
