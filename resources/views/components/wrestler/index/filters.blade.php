<div class="mb-10">
    <x-form.inputs.select name="status" label="Status"
                          :options="$this->filters->statuses()->pluck('label', 'value')"
                          class="form-select-solid fw-bold"/>
</div>
