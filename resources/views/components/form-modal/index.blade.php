<x-modal {{ $attributes->class('[color-scheme:dark] form-modal [&_label]:!text-muted-foreground [&_input]:!bg-surface [&_select]:!bg-surface [&_textarea]:!bg-surface') }}>
    <div class="flex flex-col space-y-4">{{ $slot }}</div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
