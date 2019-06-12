import $ from "jquery";
$.extend(true, $.fn.dataTable.defaults, {
    processing: true,
    serverSide: true,
    sortable: true,
    orderable: true,
    searchable: true,
    responsive: true,
    language: {
        search: "_INPUT_",
        searchPlaceholder: "Search by any column value...",
        lengthMenu: "_MENU_"
    },
    columnDefs: [
        {
            targets: "_all",
            defaultContent: '<em class="text-muted">(none)</em>'
        }
    ],
    lengthMenu: [
        [10, 25, 50, 100],
        ["10 per page", "25 per page", "50 per page", "100 per page"]
    ],
    dom:
        "<'row be-datatable-header'<'col-sm-6'f><'col-sm-6'l>>" +
        "<'row be-datatable-body'<'col-sm-12'tr>>" +
        "<'row be-datatable-footer'<'col-sm-5'i><'col-sm-7'p>>"
});
