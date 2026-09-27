<x-modal {{ $attributes->class('[color-scheme:dark] form-modal [&_label]:!text-ringside-muted [&_input]:!rounded-none [&_input]:!border-ringside-outline [&_input]:!bg-ringside-surface [&_input]:!text-ringside-ink [&_input]:!shadow-none [&_select]:!rounded-none [&_select]:!border-ringside-outline [&_select]:!bg-ringside-surface [&_select]:!text-ringside-ink [&_select]:!shadow-none [&_textarea]:!rounded-none [&_textarea]:!border-ringside-outline [&_textarea]:!bg-ringside-surface [&_textarea]:!text-ringside-ink [&_textarea]:!shadow-none') }}>
    <div class="flex flex-col space-y-4">{{ $slot }}</div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
