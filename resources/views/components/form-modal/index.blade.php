<x-modal {{ $attributes->class('[color-scheme:dark] form-modal [&_label]:!text-ringside-muted [&_input]:!bg-ringside-surface [&_select]:!bg-ringside-surface [&_textarea]:!bg-ringside-surface') }}>
    <div class="flex flex-col space-y-4">{{ $slot }}</div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
