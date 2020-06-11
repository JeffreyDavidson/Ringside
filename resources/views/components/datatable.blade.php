<div class="dataTables_wrapper">
    <div class="row">
        <div class="col-sm-12">
            <table {{ $attributes->merge(['class' => 'table table-hover table-bordered dataTable']) }}>
                {{ $slot }}
            </table>
        </div>
    </div>
    @if ($collection->isNotEmpty())
        <div class="row">
            <div class="col-sm-12 col-md-5">
                <div class="dataTables_paginate">
                    {{ $collection->links() }}
                </div>
            </div>
            <div class="col-sm-12 col-md-5">
                <div class="dataTables_info" role="status" aria-live="polite">
                    Showing {{ $collection->firstItem() }} to {{ $collection->lastItem() }} of
                    {{ $collection->total() }} entries
                </div>
            </div>
            <div class="col-sm-12 col-md-2">
                <div class="dataTables_length" id="employed_table_length">
                    <label>
                        <select wire:model="perPage" name="employed_table_length" aria-controls="employed_table"
                            class="custom-select custom-select-sm form-control form-control-sm">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>
    @endif
</div>
