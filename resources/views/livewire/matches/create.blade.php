<div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create A New Match Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('events.matches.store', $event) }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Match Type:"
                        id="match_type_id"
                        name="match_type_id"
                        :options="$matchTypes"
                        :selected="old('match_type_id')"
                        wire:model="matchTypeId"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Referees:"
                        id="referees"
                        name="referees"
                        :options="$referees"
                        :selected="old('referees')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Titles:"
                        id="titles"
                        name="titles"
                        :options="$titles"
                        :selected="old('titles')"
                    />
                </div>
                @if ($subViewToUse)
                    <div class="mb-10">
                        <x-dynamic-component :component="$subViewToUse" class="mt-4" />
                    </div>
                @endif
                <div class="mb-10">
                    <x-form.inputs.textarea
                        name="preview"
                        label="Preview"
                        :value="old('preview')"
                    />
                </div>
        </div>
        <div class="card-footer">
            <x-form.buttons.submit />
            <x-form.buttons.reset />
        </div>
        </form>
</div>
