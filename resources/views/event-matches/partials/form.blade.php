<div class="mb-10">
    <x-form.inputs.select label="Match Type:" id="match_type_id" name="match_type_id" :options="$matchTypes" :selected="old('match_type_id')" wire:model="matchTypeId"/>
</div>

<div class="mb-10">
    <x-form.inputs.select label="Referees:" id="referees" name="referees" :options="$referees" :selected="old('referees')"/>
</div>

<div class="mb-10">
    <x-form.inputs.select label="Titles:" id="titles" name="titles" :options="$titles" :selected="old('titles')"/>
</div>

<div class="mb-10">
    @include($subViewToUse)
</div>

<div class="mb-10">
    <x-form.inputs.textarea name="preview" label="Preview" :value="old('preview')"/>
</div>
