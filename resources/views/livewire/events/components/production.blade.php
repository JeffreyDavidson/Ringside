<section
    id="production"
    class="border-border mt-8 border-t pt-8"
    x-data="{ dirty: false }"
    x-on:input="dirty = true"
    x-on:change="dirty = true"
    x-on:click="if ($event.target.closest('button[type=button]')) dirty = true;"
    x-on:beforeunload.window="
        if (dirty) {
            $event.preventDefault();
            $event.returnValue = '';
        }
    "
    x-on:production-saved.window="dirty = false"
>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl">{{ __('production.title') }}</h2>
            <p class="text-muted-foreground mt-2 max-w-2xl">{{ __('production.editor_help') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a class="pack-button" href="{{ route('crew.contacts.index') }}">{{ __('production.directory') }}</a
            ><a
                class="pack-button"
                href="{{ route('events.production-sheet.show', $event) }}"
                x-on:click="if (dirty && ! confirm(@js(__('event-pack.discard_prompt')))) $event.preventDefault()"
                >{{ __('production.sheet') }}</a>
        </div>
    </div>
    @if ($errors->any())
        <div role="alert" class="pack-error mt-5">
            <x-heroicon-o-exclamation-circle class="pack-alert-icon" />
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    @if ($saved)
        <p role="status" class="pack-notice pack-success mt-4">{{ __('production.saved') }}</p>
    @endif
    <p class="text-muted-foreground mt-4">
        {{ __('production.review_counts', ['calls' => $missingCalls, 'owners' => $missingOwners]) }}
    </p>
    <form wire:submit="save" class="mt-6 flex flex-col gap-6">
        <div class="flex flex-wrap items-end gap-4">
            <label class="flex flex-col gap-2"
                >{{ __('production.search_contacts') }}<input
                    wire:model.live.debounce.300ms="search"
                    maxlength="160"
                    class="border-border bg-surface min-h-11 border p-3"
            /></label>
            <x-button variant="secondary" wire:click="addAssignment">{{ __('production.add_assignment') }}</x-button>
        </div>
        <p class="text-muted-foreground">{{ __('production.search_limit') }}</p>
        <div class="divide-border divide-y">
            @forelse ($assignments as $index => $assignment)
                <article wire:key="assignment-{{ $assignment['id'] }}" class="flex flex-col gap-4 py-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label class="flex min-w-0 flex-col gap-2"
                            >{{ __('production.contact') }}<select
                                wire:model="assignments.{{ $index }}.contact_id"
                                class="border-border bg-surface min-h-11 min-w-0 border p-3"
                            >
                                <option value="0">{{ __('production.choose_contact') }}</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}">
                                        {{ $contact->name }}{{ $contact->archived_at ? ' · '.__('production.archived') : '' }}
                                    </option>
                                @endforeach</select
                        ></label>
                        <label class="flex flex-col gap-2"
                            >{{ __('production.role') }}<select
                                wire:model="assignments.{{ $index }}.role"
                                class="border-border bg-surface min-h-11 border p-3"
                            >
                                @foreach (\App\Enums\Crew\CrewRole::cases() as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach</select
                        ></label>
                        <label class="flex flex-col gap-2"
                            >{{ __('production.station') }}<input
                                wire:model="assignments.{{ $index }}.station"
                                maxlength="160"
                                class="border-border bg-surface min-h-11 border p-3"
                        /></label>
                        <label class="flex min-w-0 flex-col gap-2"
                            >{{ __('production.call_time') }}<input
                                type="datetime-local"
                                wire:model="assignments.{{ $index }}.call_at"
                                class="border-border bg-surface min-h-11 min-w-0 border p-3"
                        /></label>
                        <label class="flex flex-col gap-2"
                            >{{ __('production.timezone') }}<input
                                wire:model="assignments.{{ $index }}.timezone"
                                class="border-border bg-surface min-h-11 border p-3"
                        /></label>
                        <label class="flex flex-col gap-2"
                            >{{ __('production.offset') }}<input
                                wire:model="assignments.{{ $index }}.offset"
                                placeholder="-04:00"
                                class="border-border bg-surface min-h-11 border p-3"
                        /></label>
                        @foreach (['setup', 'during', 'strike'] as $phase)
                            <label class="flex flex-col gap-2"
                                >{{ __('production.'.$phase) }}<textarea
                                    wire:model="assignments.{{ $index }}.{{ $phase }}"
                                    maxlength="2000"
                                    class="border-border bg-surface border p-3"
                                ></textarea>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($assignment['name'])
                            <span class="text-muted-foreground"
                                >{{ __('production.snapshot_name') }}: {{ $assignment['name'] }}</span
                            ><x-button
                                variant="secondary"
                                wire:click="refreshName('{{ $assignment['id'] }}')"
                                >{{ in_array($assignment['id'], $refreshNames, true) ? __('production.refresh_pending') : __('production.refresh_name') }}</x-button>
                        @endif
                        <x-button
                            variant="secondary"
                            wire:click="removeAssignment('{{ $assignment['id'] }}')"
                        >{{ __('production.remove_assignment') }}</x-button>
                    </div>
                </article>
            @empty
                <p class="text-muted-foreground py-5">{{ __('production.empty_assignments') }}</p>
            @endforelse
        </div>
        <div class="flex flex-col gap-4">
            @foreach ($items as $item)
                <details
                    wire:key="cues-{{ $item['key'] }}"
                    class="border-border border-y p-4"
                    @if (collect($cues)->where('item_key', $item['key'])->isNotEmpty()) open @endif
                >
                    <summary class="min-h-11 cursor-pointer py-2 font-semibold">
                        {{ $item['label'] }} · {{ __('production.cues') }} {{ collect($cues)->where('item_key', $item['key'])->count() }}
                    </summary>
                    @foreach ($cues as $index => $cue)
                        @if ($cue['item_key'] === $item['key'])
                            <article
                                wire:key="cue-{{ $cue['id'] }}"
                                class="border-border mt-4 flex flex-col gap-4 border-t pt-5"
                            >
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @foreach (['subject', 'trigger', 'instruction', 'pronunciation', 'track', 'url'] as $field)
                                        <label class="flex min-w-0 flex-col gap-2"
                                            >{{ __('production.'.$field) }}
                                            @if ($field === 'instruction')
                                                <textarea
                                                    wire:model="cues.{{ $index }}.{{ $field }}"
                                                    required
                                                    maxlength="2000"
                                                    class="border-border bg-surface border p-3"
                                                ></textarea>
                                            @else
                                                <input
                                                    wire:model="cues.{{ $index }}.{{ $field }}"
                                                    type="{{ $field === 'url' ? 'url' : 'text' }}"
                                                    @required(in_array($field, ['subject', 'trigger'], true))
                                                    maxlength="{{ in_array($field, ['subject', 'trigger'], true) ? 160 : ($field === 'url' ? 2000 : 500) }}"
                                                    class="border-border bg-surface min-h-11 min-w-0 border p-3"
                                                />
                                            @endif
                                        </label>
                                    @endforeach
                                    <label class="flex min-w-0 flex-col gap-2"
                                        >{{ __('production.owner') }}<select
                                            wire:model="cues.{{ $index }}.owner_id"
                                            class="border-border bg-surface min-h-11 min-w-0 border p-3"
                                        >
                                            <option value="">{{ __('production.unassigned') }}</option>
                                            @foreach ($assignments as $owner)
                                                <option value="{{ $owner['id'] }}">
                                                    {{ $contacts->firstWhere('id', $owner['contact_id'])?->name ?? __('production.choose_contact') }} · {{ \App\Enums\Crew\CrewRole::tryFrom($owner['role'])?->label() ?? $owner['role'] }} · {{ $owner['station'] }}
                                                </option>
                                            @endforeach
                                            @if ($cue['owner_id'] && ! in_array($cue['owner_id'], array_column($assignments, 'id'), true))
                                                <option value="{{ $cue['owner_id'] }}">
                                                    {{ $cue['owner_name'] }} · {{ __('production.removed_owner') }}
                                                </option>
                                            @endif</select
                                    ></label>
                                    <label class="flex flex-col gap-2"
                                        >{{ __('production.target_role') }}<select
                                            wire:model="cues.{{ $index }}.role"
                                            class="border-border bg-surface min-h-11 border p-3"
                                        >
                                            <option value="">{{ __('production.any_role') }}</option>
                                            @foreach (\App\Enums\Crew\CrewRole::cases() as $role)
                                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                            @endforeach</select
                                    ></label>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <x-button
                                        variant="secondary"
                                        wire:click="moveCue('{{ $cue['id'] }}', -1)"
                                        >{{ __('production.move_up') }}</x-button
                                    ><x-button
                                        variant="secondary"
                                        wire:click="moveCue('{{ $cue['id'] }}', 1)"
                                        >{{ __('production.move_down') }}</x-button
                                    ><x-button
                                        variant="secondary"
                                        wire:click="removeCue('{{ $cue['id'] }}')"
                                        >{{ __('production.remove_cue') }}</x-button>
                                </div>
                            </article>
                        @endif
                    @endforeach
                    <x-button
                        variant="secondary"
                        class="mt-4"
                        wire:click="addCue('{{ $item['key'] }}')"
                    >{{ __('production.add_cue') }}</x-button>
                </details>
            @endforeach
        </div>
        @php $retained = array_filter($data['content']['production']['cues'] ?? [], fn ($cue) => ! in_array($cue['item_key'], array_column($items, 'key'), true)); @endphp
        @if ($retained)
            <details>
                <summary class="min-h-11 cursor-pointer py-3">
                    {{ __('production.retained_cues') }} ({{ count($retained) }})
                </summary>
                @foreach ($retained as $cue)
                    <div class="py-3">
                        <p>{{ $cue['item_key'] }} · {{ $cue['subject'] }} · {{ $cue['instruction'] }}</p>
                        <label class="flex flex-col gap-2"
                            >{{ __('production.reattach_to') }}<select
                                wire:model="retainedTargets.{{ $cue['id'] }}"
                                class="border-border bg-surface min-h-11 border p-3"
                            >
                                <option value="">{{ __('production.choose_item') }}</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item['key'] }}">{{ $item['label'] }}</option>
                                @endforeach</select></label
                        ><x-button
                            variant="secondary"
                            wire:click="restoreCue('{{ $cue['id'] }}')"
                            >{{ __('production.reattach') }}</x-button>
                    </div>
                @endforeach
            </details>
        @endif
        <label class="flex flex-col gap-2"
            >{{ __('production.private_notes') }}<textarea
                wire:model="notes"
                maxlength="10000"
                class="border-border bg-surface border p-3"
            ></textarea>
        </label>
        @if ($data['needsReview'])
            <label class="flex gap-3"><input type="checkbox" wire:model="reviewed" />{{ __('event-pack.reviewed') }}</label>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('production.save') }}</x-button
            ><x-button
                variant="secondary"
                wire:click="reloadSaved"
                wire:confirm="{{ __('event-pack.discard_prompt') }}"
                >{{ __('production.reload') }}</x-button
            ><span wire:dirty class="text-warning">{{ __('event-pack.unsaved') }}</span>
        </div>
    </form>
</section>
