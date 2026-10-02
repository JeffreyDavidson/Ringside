@use('App\Enums\Roster\BookableRosterKind')

<x-form-modal>
    <x-form.error name="form.configuration" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="match-setup-grid">
        <x-form-modal.modal-input>
            <x-form.inputs.select
                label="Match Type"
                wire:model.live="form.matchType"
                :options="$this->getMatchTypes"
                placeholder="Select a match type"
            />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.select
                label="Match Stipulation"
                wire:model="form.matchStipulationId"
                :options="$this->getMatchStipulations"
                placeholder="Standard match"
            />
        </x-form-modal.modal-input>
    </div>

    {{-- Dynamic Competitor Selection Based on Match Type --}}
    @if ($form->matchType)
        <section class="space-y-3" aria-labelledby="match-competitors-heading">
            <h3
                id="match-competitors-heading"
                class="border-ringside-outline text-ringside-ink border-b pb-2 text-sm font-semibold"
            >
                Competitors
            </h3>
            @switch ($this->competitorSelectionLayout)
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::Singles)
                    {{-- Singles Match: 2 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div
                            class="match-competitors-grid grid grid-cols-1 gap-4 sm:grid-cols-2"
                            data-test="match-competitors-grid"
                        >
                            <x-form.inputs.roster-combobox
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::TagTeam)
                    {{-- Tag Team Match: 2 sides, wrestlers or tag teams --}}
                    <x-form-modal.modal-input>
                        <div
                            class="match-competitors-grid grid grid-cols-1 gap-4 sm:grid-cols-2"
                            data-test="match-competitors-grid"
                        >
                            <div class="space-y-3">
                                <p class="text-ringside-ink text-sm font-semibold">Team A</p>
                                <x-form.inputs.roster-combobox
                                    label="Wrestlers"
                                    wire:model="form.competitors.0.wrestlers"
                                    :kind="BookableRosterKind::Wrestlers"
                                    :labels="$this->selectedRosterLabels['wrestlers']"
                                    multiple
                                />
                                <x-form.inputs.roster-combobox
                                    label="Tag Teams"
                                    wire:model="form.competitors.0.tag_teams"
                                    :kind="BookableRosterKind::TagTeams"
                                    :labels="$this->selectedRosterLabels['tag_teams']"
                                    multiple
                                />
                            </div>
                            <div class="space-y-3">
                                <p class="text-ringside-ink text-sm font-semibold">Team B</p>
                                <x-form.inputs.roster-combobox
                                    label="Wrestlers"
                                    wire:model="form.competitors.1.wrestlers"
                                    :kind="BookableRosterKind::Wrestlers"
                                    :labels="$this->selectedRosterLabels['wrestlers']"
                                    multiple
                                />
                                <x-form.inputs.roster-combobox
                                    label="Tag Teams"
                                    wire:model="form.competitors.1.tag_teams"
                                    :kind="BookableRosterKind::TagTeams"
                                    :labels="$this->selectedRosterLabels['tag_teams']"
                                    multiple
                                />
                            </div>
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::TripleThreat)
                    {{-- Triple Threat: 3 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div
                            class="match-competitors-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            data-test="match-competitors-grid"
                        >
                            <x-form.inputs.roster-combobox
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 3"
                                wire:model="form.competitors.2.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::FatalFourWay)
                    {{-- Fatal Four Way: 4 sides, 1 wrestler each --}}
                    <x-form-modal.modal-input>
                        <div
                            class="match-competitors-grid grid grid-cols-1 gap-4 sm:grid-cols-2"
                            data-test="match-competitors-grid"
                        >
                            <x-form.inputs.roster-combobox
                                label="Competitor 1"
                                wire:model="form.competitors.0.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 2"
                                wire:model="form.competitors.1.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 3"
                                wire:model="form.competitors.2.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                            <x-form.inputs.roster-combobox
                                label="Competitor 4"
                                wire:model="form.competitors.3.wrestlers.0"
                                :kind="BookableRosterKind::Wrestlers"
                                :labels="$this->selectedRosterLabels['wrestlers']"
                            />
                        </div>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::BattleRoyal)
                    {{-- Battle Royal: Multiple individual wrestlers --}}
                    <x-form-modal.modal-input>
                        <x-form.inputs.roster-combobox
                            label="Competitors (Select Multiple)"
                            wire:model="form.competitors.0.wrestlers"
                            :kind="BookableRosterKind::Wrestlers"
                            :labels="$this->selectedRosterLabels['wrestlers']"
                            multiple
                        />
                        <p class="text-ringside-muted mt-1 text-sm">Select all wrestlers participating in this match</p>
                    </x-form-modal.modal-input>
                    @break
                @case (\App\Livewire\Matches\Enums\CompetitorSelectionLayout::Generic)
                    <x-form-modal.modal-input>
                        <div
                            class="match-competitors-grid grid grid-cols-1 gap-4 sm:grid-cols-2"
                            data-test="match-competitors-grid"
                        >
                            @foreach ($form->competitors as $sideIndex => $competitors)
                                <div wire:key="competitor-side-{{ $sideIndex }}" class="space-y-3">
                                    <p class="text-ringside-ink text-sm font-semibold">Side {{ $loop->iteration }}</p>
                                    <x-form.inputs.roster-combobox
                                        label="Wrestlers"
                                        wire:model="form.competitors.{{ $sideIndex }}.wrestlers"
                                        :kind="BookableRosterKind::Wrestlers"
                                        :labels="$this->selectedRosterLabels['wrestlers']"
                                        multiple
                                    />

                                    @if ($this->matchTypeAllowsTagTeams)
                                        <x-form.inputs.roster-combobox
                                            label="Tag Teams"
                                            wire:model="form.competitors.{{ $sideIndex }}.tag_teams"
                                            :kind="BookableRosterKind::TagTeams"
                                            :labels="$this->selectedRosterLabels['tag_teams']"
                                            multiple
                                        />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-form-modal.modal-input>
                    @break

            @endswitch
        </section>
    @else
        {{-- No match type selected - show helper text --}}
        <x-form-modal.modal-input>
            <div class="text-ringside-muted py-8 text-center">
                <p class="text-sm">Select a match type to configure competitors</p>
            </div>
        </x-form-modal.modal-input>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="match-officials-grid">
        <x-form-modal.modal-input>
            <x-form.inputs.roster-combobox
                label="Referees"
                wire:model="form.referees"
                :kind="BookableRosterKind::Referees"
                :labels="$this->selectedRosterLabels['referees']"
                multiple
            />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.select label="Titles" wire:model="form.titles" :options="$this->getTitles" multiple />
        </x-form-modal.modal-input>
    </div>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="Preview" wire:model="form.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
