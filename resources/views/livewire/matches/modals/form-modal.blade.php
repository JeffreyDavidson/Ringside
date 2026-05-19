<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.select label="Match Type" wire:model.live="form.matchType" :options="$this->getMatchTypes" />
    </x-form-modal.modal-input>

    {{-- Dynamic Competitor Selection Based on Match Type --}}
    @if ($form->matchType)
        <div class="space-y-4">
            @if (str_contains($this->matchTypeName, 'singles'))
                {{-- Singles Match: 2 sides, 1 wrestler each --}}
                <x-form-modal.modal-input>
                    <div class="grid grid-cols-2 gap-4">
                        <x-form.inputs.select 
                            label="Competitor 1" 
                            wire:model="form.competitors.0.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 2" 
                            wire:model="form.competitors.1.wrestlers.0" 
                            :options="$this->getWrestlers" />
                    </div>
                </x-form-modal.modal-input>

            @elseif (str_contains($this->matchTypeName, 'tag') || str_contains($this->matchTypeName, 'team'))
                {{-- Tag Team Match: 2 sides, wrestlers or tag teams --}}
                <x-form-modal.modal-input>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="font-medium text-sm">Team A</label>
                            <x-form.inputs.select 
                                label="Wrestlers" 
                                wire:model="form.competitors.0.wrestlers" 
                                :options="$this->getWrestlers" 
                                multiple />
                            <x-form.inputs.select 
                                label="Tag Teams" 
                                wire:model="form.competitors.0.tag_teams" 
                                :options="$this->getTagTeams" />
                        </div>
                        <div class="space-y-2">
                            <label class="font-medium text-sm">Team B</label>
                            <x-form.inputs.select 
                                label="Wrestlers" 
                                wire:model="form.competitors.1.wrestlers" 
                                :options="$this->getWrestlers" 
                                multiple />
                            <x-form.inputs.select 
                                label="Tag Teams" 
                                wire:model="form.competitors.1.tag_teams" 
                                :options="$this->getTagTeams" />
                        </div>
                    </div>
                </x-form-modal.modal-input>

            @elseif (str_contains($this->matchTypeName, 'triple') || str_contains($this->matchTypeName, 'three'))
                {{-- Triple Threat: 3 sides, 1 wrestler each --}}
                <x-form-modal.modal-input>
                    <div class="grid grid-cols-3 gap-4">
                        <x-form.inputs.select 
                            label="Competitor 1" 
                            wire:model="form.competitors.0.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 2" 
                            wire:model="form.competitors.1.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 3" 
                            wire:model="form.competitors.2.wrestlers.0" 
                            :options="$this->getWrestlers" />
                    </div>
                </x-form-modal.modal-input>

            @elseif (str_contains($this->matchTypeName, 'fatal') || str_contains($this->matchTypeName, 'four'))
                {{-- Fatal Four Way: 4 sides, 1 wrestler each --}}
                <x-form-modal.modal-input>
                    <div class="grid grid-cols-2 gap-4">
                        <x-form.inputs.select 
                            label="Competitor 1" 
                            wire:model="form.competitors.0.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 2" 
                            wire:model="form.competitors.1.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 3" 
                            wire:model="form.competitors.2.wrestlers.0" 
                            :options="$this->getWrestlers" />
                        <x-form.inputs.select 
                            label="Competitor 4" 
                            wire:model="form.competitors.3.wrestlers.0" 
                            :options="$this->getWrestlers" />
                    </div>
                </x-form-modal.modal-input>

            @elseif (str_contains($this->matchTypeName, 'battle') || str_contains($this->matchTypeName, 'rumble') || str_contains($this->matchTypeName, 'royal'))
                {{-- Battle Royal: Multiple individual wrestlers --}}
                <x-form-modal.modal-input>
                    <x-form.inputs.select 
                        label="Competitors (Select Multiple)" 
                        wire:model="form.competitors.0.wrestlers" 
                        :options="$this->getWrestlers" 
                        multiple />
                    <p class="text-sm text-gray-600 mt-1">Select all wrestlers participating in this match</p>
                </x-form-modal.modal-input>

            @else
                {{-- Default/Unknown Match Type: Generic competitor selection --}}
                <x-form-modal.modal-input>
                    <x-form.inputs.select label="Wrestlers" wire:model="form.competitors.0.wrestlers" :options="$this->getWrestlers" multiple />
                </x-form-modal.modal-input>
                
                @if ($this->matchTypeAllowsTagTeams)
                    <x-form-modal.modal-input>
                        <x-form.inputs.select label="Tag Teams" wire:model="form.competitors.0.tag_teams" :options="$this->getTagTeams" multiple />
                    </x-form-modal.modal-input>
                @endif
            @endif
        </div>
    @else
        {{-- No match type selected - show helper text --}}
        <x-form-modal.modal-input>
            <div class="text-center py-8 text-gray-500">
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
