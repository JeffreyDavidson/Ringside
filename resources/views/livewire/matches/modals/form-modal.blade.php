<x-form-modal>
    <x-form.error name="form.configuration" show-icon />

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Match Type" wire:model.live="form.matchType" :options="$this->getMatchTypes" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select
            label="Match Stipulation"
            wire:model="form.matchStipulationId"
            :options="$this->getMatchStipulations"
            placeholder="Standard match"
        />
    </x-form-modal.modal-input>

    {{-- Dynamic Competitor Selection Based on Match Type --}}
    @if ($form->matchType)
        <div class="space-y-4">
            @switch ($this->competitorSelectionLayout)
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::Singles)
                    {{-- Singles Match: 2 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.inputs.select
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::TagTeam)
                    {{-- Tag Team Match: 2 sides, wrestlers or tag teams --}}
                    <x-form-modal.modal-input>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Team A</label>
                                <x-form.inputs.select
                                    label="Wrestlers"
                                    wire:model="form.competitors.0.wrestlers"
                                    :options="$this->getWrestlers"
                                    multiple
                                />
                                <x-form.inputs.select
                                    label="Tag Teams"
                                    wire:model="form.competitors.0.tag_teams"
                                    :options="$this->getTagTeams"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Team B</label>
                                <x-form.inputs.select
                                    label="Wrestlers"
                                    wire:model="form.competitors.1.wrestlers"
                                    :options="$this->getWrestlers"
                                    multiple
                                />
                                <x-form.inputs.select
                                    label="Tag Teams"
                                    wire:model="form.competitors.1.tag_teams"
                                    :options="$this->getTagTeams"
                                />
                            </div>
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::TripleThreat)
                    {{-- Triple Threat: 3 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div class="grid grid-cols-3 gap-4">
                            <x-form.inputs.select
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 3"
                                wire:model="form.competitors.2.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::FatalFourWay)
                    {{-- Fatal Four Way: 4 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.inputs.select
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 3"
                                wire:model="form.competitors.2.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                            <x-form.inputs.select
                                label="Competitor 4"
                                wire:model="form.competitors.3.wrestlers.0"
                                :options="$this->getWrestlers"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::BattleRoyal)
                    {{-- Battle Royal: Multiple individual wrestlers --}}
                    <x-form-modal.modal-input>
                        <x-form.inputs.select
                            label="Competitors (Select Multiple)"
                            wire:model="form.competitors.0.wrestlers"
                            :options="$this->getWrestlers"
                            multiple
                        />
                        <p class="mt-1 text-sm text-gray-600">Select all wrestlers participating in this match</p>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::Generic)
                    <x-form-modal.modal-input>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            @foreach ($form->competitors as $sideIndex => $competitors)
                                <div wire:key="competitor-side-{{ $sideIndex }}" class="space-y-2">
                                    <p class="text-sm font-medium">Side {{ $loop->iteration }}</p>
                                    <x-form.inputs.select
                                        label="Wrestlers"
                                        wire:model="form.competitors.{{ $sideIndex }}.wrestlers"
                                        :options="$this->getWrestlers"
                                        multiple
                                    />

                                    @if ($this->matchTypeAllowsTagTeams)
                                        <x-form.inputs.select
                                            label="Tag Teams"
                                            wire:model="form.competitors.{{ $sideIndex }}.tag_teams"
                                            :options="$this->getTagTeams"
                                            multiple
                                        />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-form-modal.modal-input>
                    @break

            @endswitch
        </div>
    @else
        {{-- No match type selected - show helper text --}}
        <x-form-modal.modal-input>
            <div class="py-8 text-center text-gray-500">
                <p class="text-sm">Select a match type to configure competitors</p>
            </div>
        </x-form-modal.modal-input>
    @endif

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Referees" wire:model="form.referees" :options="$this->getReferees" multiple />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Titles" wire:model="form.titles" :options="$this->getTitles" multiple />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="Preview" wire:model="form.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
