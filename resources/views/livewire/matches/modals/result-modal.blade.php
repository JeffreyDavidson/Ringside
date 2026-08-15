<x-modal size="lg">
    <div class="space-y-6">
        @error('outcome')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                {{ $message }}
            </div>
        @enderror

        <div class="grid gap-4 md:grid-cols-2">
            <x-form.inputs.select
                label="Finish"
                wire:model.live="finish"
                :options="$this->finishOptions"
                placeholder="Select a finish"
            />

            <x-form.inputs.select
                label="Winning Side"
                wire:model="winningSideId"
                :options="$this->sideOptions"
                placeholder="No winning side"
            />
        </div>

        @if ($this->match->match_type->recordsIndividualEliminations())
            <section class="space-y-3">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Eliminations</h4>
                    <p class="text-xs text-gray-600">
                        Record each eliminated competitor in order. Leave the winner without an elimination order.
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-xs font-medium text-gray-600">
                            <tr>
                                <th class="px-4 py-2.5">Competitor</th>
                                <th class="px-4 py-2.5">Order</th>
                                <th class="px-4 py-2.5">Eliminated By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($this->match->competitors as $competitor)
                                <tr wire:key="result-competitor-{{ $competitor->id }}">
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ $competitor->competitor->name }}
                                    </td>
                                    <td class="w-28 px-4 py-3">
                                        <input
                                            type="number"
                                            min="1"
                                            wire:model="eliminations.{{ $competitor->id }}.order"
                                            class="block h-8.5 w-full rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-gray-500 focus:ring-gray-500"
                                            aria-label="Elimination order for {{ $competitor->competitor->name }}"
                                        />
                                        @error("eliminations.{$competitor->id}.order")
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-form.inputs.select
                                            wire:model="eliminations.{{ $competitor->id }}.eliminatedById"
                                            :options="collect($this->competitorOptions)->except($competitor->id)->all()"
                                            placeholder="Not recorded"
                                            size="sm"
                                            aria-label="Eliminator for {{ $competitor->competitor->name }}"
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
            <x-buttons.light wire:click="$dispatch('closeModal')">Cancel</x-buttons.light>
            <x-buttons.primary wire:click="save" wire:loading.attr="disabled" wire:target="save">
                Save Result
            </x-buttons.primary>
        </div>
    </x-slot:footer>
</x-modal>
