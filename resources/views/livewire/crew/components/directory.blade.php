<div class="mx-auto flex max-w-5xl flex-col gap-6">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl">{{ __('production.directory') }}</h1>
            <p class="text-ringside-muted mt-3 max-w-2xl">{{ __('production.private_help') }}</p>
        </div>
        @can('create', \App\Models\Crew\CrewContact::class)
            <x-button wire:click="newContact">{{ __('production.add_contact') }}</x-button>
        @else
            <p class="text-ringside-warning">{{ __('production.choose_promotion') }}</p>
        @endcan
    </header>
    @if ($errors->any())
        <div role="alert" class="border-ringside-error bg-ringside-error-surface text-ringside-error border p-4">
            <x-heroicon-o-exclamation-circle class="inline h-5 w-5" />
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if ($editing)
        <form wire:submit="save" class="border-ringside-line grid gap-4 border-y py-6 sm:grid-cols-2">
            <label class="flex flex-col gap-2"
                >{{ __('production.name') }}<input
                    wire:model="name"
                    required
                    maxlength="160"
                    class="border-ringside-line bg-ringside-surface min-h-11 border p-3"
            /></label>
            <label class="flex flex-col gap-2"
                >{{ __('production.phone') }}<input
                    wire:model="phone"
                    maxlength="80"
                    class="border-ringside-line bg-ringside-surface min-h-11 border p-3"
            /></label>
            <label class="flex flex-col gap-2"
                >{{ __('production.email') }}<input
                    wire:model="email"
                    type="email"
                    maxlength="255"
                    class="border-ringside-line bg-ringside-surface min-h-11 border p-3"
            /></label>
            <label class="flex flex-col gap-2"
                >{{ __('production.contact_notes') }}<textarea
                    wire:model="notes"
                    maxlength="2000"
                    class="border-ringside-line bg-ringside-surface border p-3"
                ></textarea>
            </label>
            <div class="flex flex-wrap gap-3 sm:col-span-2">
                <x-button type="submit" wire:loading.attr="disabled">{{ __('production.save_contact') }}</x-button
                ><x-button variant="secondary" wire:click="cancel">{{ __('production.cancel') }}</x-button>
            </div>
        </form>
    @endif
    <div class="flex flex-wrap items-end gap-4">
        <label class="flex flex-col gap-2"
            >{{ __('production.search_contacts') }}<input
                wire:model.live.debounce.300ms="search"
                maxlength="160"
                class="border-ringside-line bg-ringside-surface min-h-11 border p-3"
        /></label>
        <label class="flex min-h-11 items-center gap-3"><input type="checkbox" wire:model.live="showArchived" />{{ __('production.include_archived') }}</label>
        <p class="text-ringside-muted">{{ __('production.search_limit') }}</p>
    </div>
    <div class="divide-ringside-line divide-y">
        @forelse ($contacts as $contact)
            <article wire:key="crew-{{ $contact->id }}" class="flex flex-wrap items-center justify-between gap-4 py-5">
                <div>
                    <h2 class="text-xl font-semibold break-words">{{ $contact->name }}</h2>
                    @if ($contact->archived_at)
                        <p class="text-ringside-warning">{{ __('production.archived') }}</p>
                    @endif
                </div>
                @if (! $contact->archived_at)
                    <div class="flex flex-wrap gap-3">
                        <x-button
                            variant="secondary"
                            wire:click="edit({{ $contact->id }})"
                            >{{ __('production.edit') }}</x-button
                        ><x-button
                            variant="secondary"
                            wire:click="archive({{ $contact->id }}, {{ $contact->version }})"
                            wire:confirm="{{ __('production.archive_confirm') }}"
                            >{{ __('production.archive') }}</x-button>
                    </div>
                @endif
            </article>
        @empty
            <p class="text-ringside-muted py-6">{{ __('production.empty_contacts') }}</p>
        @endforelse
    </div>
</div>
