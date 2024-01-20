<button type="button" class="btn btn-light-primary me-3">
    <i class="ki-duotone ki-filter fs-2"><span class="path1"></span><span class="path2"></span></i> Filter
</button>

<x-filters>
    <div class="px-7 py-5">
        <div class="mb-10">
            <x-form.inputs.select name="status" label="Status" :options="[]" class="form-select-solid fw-bold"/>
        </div>
    </div>
</x-filters>


