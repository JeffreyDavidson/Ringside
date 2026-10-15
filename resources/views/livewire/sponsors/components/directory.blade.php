<div x-data="sponsorTracking" class="mx-auto flex max-w-5xl flex-col gap-6 pb-8">
    <header class="border-border flex flex-col gap-3 border-b pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="font-display text-2xl break-words lg:text-3xl">
                {{ $selected?->name ?? __('sponsors.directory') }}
            </h1>
            <p class="text-muted-foreground mt-2 max-w-prose text-sm">{{ __('sponsors.private_help') }}</p>
        </div>
        <x-button
            wire:click="newSponsor"
            wire:loading.attr="disabled"
            x-bind:disabled="! online || requestFailed"
        >{{ __('sponsors.new_sponsor') }}</x-button>
    </header>
    <div x-cloak x-show="! online || requestFailed" role="alert" class="border-warning border p-4">
        <p>{{ __('sponsors.online_help') }}</p>
        <a
            href="{{ route('sponsors.index') }}"
            class="inline-block min-h-11 py-3 underline"
        >{{ __('sponsors.reload') }}</a>
    </div>
    @if ($notice)
        <p role="status" class="border-success border p-4">{{ $notice }}</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="border-error bg-error-surface text-error border p-4">
            <p class="flex gap-2 font-semibold">
                <x-heroicon-o-exclamation-circle
                    class="size-5 shrink-0"
                    aria-hidden="true"
                />{{ __('event-pack.save_failed') }}
            </p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if ($formShown)
        <form
            wire:submit="save"
            x-data="{ dirty: false }"
            @input="dirty = true"
            @beforeunload.window="
                if (dirty) {
                    $event.preventDefault();
                    $event.returnValue = '';
                }
            "
            class="border-border bg-surface-panel border-y p-4 sm:p-6"
            data-sponsor-draft
        >
            <fieldset x-bind:disabled="! online || requestFailed" class="flex min-w-0 flex-col gap-5">
                <legend class="mb-4 text-xl font-semibold">
                    {{ __($draftId === null ? 'sponsors.new_sponsor' : 'sponsors.edit_sponsor') }}
                </legend>
                <p x-cloak x-show="dirty" class="text-warning">{{ __('event-pack.unsaved') }}</p>
                <x-form.inputs.text class="min-h-11" wire:model="form.name" :label="__('sponsors.name')" />
                <div class="grid min-w-0 gap-5 md:grid-cols-2">
                    <x-form.inputs.text
                        class="min-h-11"
                        wire:model="form.contactName"
                        :label="__('sponsors.contact_name')"
                    />
                    <x-form.inputs.text class="min-h-11" wire:model="form.email" :label="__('sponsors.email')" />
                    <x-form.inputs.text class="min-h-11" wire:model="form.phone" :label="__('sponsors.phone')" />
                </div>
                <x-form.inputs.textarea wire:model="form.notes" :label="__('sponsors.private_notes')" rows="3" />
                <label class="flex min-h-11 items-center gap-3"
                    ><input
                        type="checkbox"
                        class="size-5"
                        wire:model="form.archived"
                    />{{ __('sponsors.archive') }}</label>
                <p class="text-muted-foreground max-w-prose text-sm">{{ __('sponsors.sponsor_archive_help') }}</p>
                <div class="flex flex-wrap gap-3">
                    <x-button type="submit" wire:loading.attr="disabled">{{ __('sponsors.save_sponsor') }}</x-button
                    ><x-button variant="secondary" wire:click="cancelDraft">{{ __('sponsors.cancel_draft') }}</x-button>
                </div>
            </fieldset>
        </form>
    @endif
    @if ($selected)
        <section class="border-border border-b pb-6">
            <p class="break-words">{{ $selected->contact_name }}</p>
            <p class="mt-2 break-all">{{ $selected->contact_email }}</p>
            <p class="mt-2 break-all">{{ $selected->contact_phone }}</p>
            <p class="mt-2 break-words whitespace-pre-wrap">{{ $selected->notes }}</p>
            <x-button
                class="mt-4"
                variant="secondary"
                wire:click="edit({{ $selected->id }})"
            >{{ __('sponsors.edit_sponsor') }}</x-button>
        </section>
        <section>
            <h2 class="text-xl font-semibold">{{ __('sponsors.sponsor_history') }}</h2>
            @forelse ($history as $promise)
                <article wire:key="sponsor-promise-{{ $promise->id }}" class="border-border border-b py-5">
                    <p class="font-semibold">
                        {{ $promise->event->name }}
                        @if ($promise->event->trashed()) ·{{ __('sponsors.archived_label') }}@endif
                    </p>
                    <p class="mt-2 break-words">{{ $promise->description }} · {{ $screen->status($promise) }}</p>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ __('sponsors.reference') }}: {{ $promise->agreement_reference ?? __('sponsors.not_set') }}
                    </p>
                    <a
                        class="inline-block min-h-11 py-3 underline underline-offset-4"
                        href="{{ route('events.sponsors.show', $promise->event_id) }}"
                    >{{ __('sponsors.open_event') }}</a>
                </article>
            @empty
                <p class="text-muted-foreground py-6">{{ __('sponsors.empty') }}</p>
            @endforelse
            {{ $history->links() }}
        </section>
    @endif
    <div class="max-w-md">
        <x-form.inputs.text class="min-h-11" wire:model.live.debounce.300ms="search" :label="__('sponsors.search')" />
    </div>
    <section class="divide-border divide-y">
        @forelse ($sponsors as $sponsor)
            <article
                wire:key="sponsor-{{ $sponsor->id }}"
                class="flex min-w-0 flex-col gap-4 py-5 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold break-words">
                        <a
                            class="underline underline-offset-4"
                            href="{{ route('sponsors.show', $sponsor) }}"
                        >{{ $sponsor->name }}</a>
                    </h2>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{ $sponsor->contact_name ?? __('sponsors.record_number', ['number' => $sponsor->id]) }}
                    </p>
                    @if ($sponsor->archived_at)
                        <p class="text-muted-foreground mt-2 text-sm">{{ __('sponsors.archived_label') }}</p>
                    @endif
                </div>
                <x-button
                    variant="secondary"
                    wire:click="edit({{ $sponsor->id }})"
                >{{ __('sponsors.edit_sponsor') }}</x-button>
            </article>
        @empty
            <p class="text-muted-foreground py-6">{{ __('sponsors.empty_sponsors') }}</p>
        @endforelse
    </section>
    {{ $sponsors->links() }}
</div>
