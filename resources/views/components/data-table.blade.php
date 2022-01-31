<div class="dataTables_wrapper">
    <div class="row">
        <div class="col-sm-12">
            <table {{ $attributes->merge(['class' => 'table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer']) }}>
                {{ $slot }}
            </table>
        </div>
    </div>
    @if ($collection->isNotEmpty())
        <div class="row">
            <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start"></div>
            <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                <div class="dataTables_paginate paging_simple_numbers">
                    {{ $collection->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
