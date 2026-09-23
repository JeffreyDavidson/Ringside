<x-modal {{ $attributes->class('!border-ringside-line !bg-ringside-surface-panel [&_h3]:!text-ringside-ink [&_label]:!text-ringside-muted [&_input]:!border-ringside-outline [&_input]:!bg-ringside-surface [&_input]:!text-ringside-ink [&_select]:!border-ringside-outline [&_select]:!bg-ringside-surface [&_select]:!text-ringside-ink [&_textarea]:!border-ringside-outline [&_textarea]:!bg-ringside-surface [&_textarea]:!text-ringside-ink !rounded-none !border') }}>
    <div class="flex flex-col space-y-4">{{ $slot }}</div>

    <x-slot:footer>
        <x-form.footer />
    </x-slot:footer>
</x-modal>
