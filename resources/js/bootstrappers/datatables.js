import $ from "jquery";
$.extend(true, $.fn.dataTable.defaults, {
    processing: true,
    serverSide: true,
    sortable: true,
    orderable: true,
    searchable: true,
    responsive: true,
    searchDelay: 100,
    pageLength: 10,
    language: {
        search: "Search:",
        lengthMenu: "_MENU_",
        sEmptyTable: "No matching records found",
        infoEmpty: "Showing 0 to 0 of 0 entries"
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
        "<'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'p><'col-sm-12 col-md-5'i><'col-sm-12 col-md-2'l>>",
    oClasses: {
        sPaging: `p-3 dataTables_paginate paging_`,
        sLength: "p-3 dataTables_length",
        sInfo: "p-3 dataTables_info"
    }
});
