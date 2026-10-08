<x-modal size="lg">
    <div class="space-y-6">
        @error('outcome')
            <div
                class="border-ringside-signal-soft bg-ringside-surface text-ringside-ink flex items-start gap-3 border px-4 py-3 text-sm"
                role="alert"
            >
                <x-heroicon-s-exclamation-circle
                    class="text-ringside-signal-soft mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>{{ $message }}</span>
            </div>
        @enderror

        <div class="grid gap-4 md:grid-cols-2">
            <x-form.inputs.select
                id="finish"
                :label="__('matches.result_modal.finish')"
                wire:model.live="form.finish"
                :options="$this->finishOptions"
                :placeholder="__('matches.result_modal.select_finish')"
            />

            <x-form.inputs.select
                id="winningSideId"
                :label="__('matches.result_modal.winning_side')"
                wire:model="form.winningSideId"
                :options="$this->sideOptions"
                :placeholder="__('matches.result_modal.no_winning_side')"
            />
        </div>

        @if ($this->match->match_type->recordsIndividualEliminations())
            <section class="space-y-3" aria-labelledby="result-eliminations-heading">
                <div>
                    <h3 id="result-eliminations-heading" class="text-ringside-ink text-sm font-semibold">
                        {{ __('matches.result_modal.eliminations') }}
                    </h3>
                    <p class="text-ringside-muted text-xs">{{ __('matches.result_modal.eliminations_hint') }}</p>
                </div>

                <div class="border-ringside-line overflow-x-auto border">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-ringside-surface text-ringside-muted text-xs font-medium">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">{{ __('matches.result_modal.competitor') }}</th>
                                <th scope="col" class="px-4 py-2.5">{{ __('matches.result_modal.order') }}</th>
                                <th scope="col" class="px-4 py-2.5">{{ __('matches.result_modal.eliminated_by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-ringside-line divide-y">
                            @foreach ($this->match->competitors as $competitor)
                                @php
                                    $orderInputId = "elimination-order-{$competitor->id}";
                                    $orderField = "form.eliminations.{$competitor->id}.order";
                                @endphp
                                <tr wire:key="result-competitor-{{ $competitor->id }}">
                                    <th scope="row" class="text-ringside-ink px-4 py-3 font-medium">
                                        {{ $competitor->competitor->name }}
                                    </th>
                                    <td class="w-28 px-4 py-3 align-top">
                                        <x-form.input
                                            type="number"
                                            min="1"
                                            size="sm"
                                            :id="$orderInputId"
                                            :name="$orderField"
                                            wire:model="form.eliminations.{{ $competitor->id }}.order"
                                            :aria-label="__('matches.result_modal.elimination_order_for', ['name' => $competitor->competitor->name])"
                                        />
                                        @error($orderField)
                                            <div id="{{ $orderInputId }}-error">
                                                <x-form.error :name="$orderField" />
                                            </div>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <x-form.inputs.select
                                            wire:model="form.eliminations.{{ $competitor->id }}.eliminatedById"
                                            :options="collect($this->competitorOptions)->except([$competitor->id])->all()"
                                            :placeholder="__('matches.result_modal.not_recorded')"
                                            size="sm"
                                            :aria-label="__('matches.result_modal.eliminator_for', ['name' => $competitor->competitor->name])"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <x-slot:footer>
        <div class="flex flex-1 justify-end gap-2">
            <x-buttons.light wire:click="$dispatch('closeModal')">{{ __('core.form.cancel') }}</x-buttons.light>
            <x-buttons.primary
                data-test="save-result"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                {{ __('matches.result_modal.save_result') }}
            </x-buttons.primary>
        </div>
    </x-slot:footer>
</x-modal>
