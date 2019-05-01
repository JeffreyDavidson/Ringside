"use strict";
var ResourceBasicTable = (function() {
    var initTable = function() {
        const table = $('[data-table="wrestlers.index"]');
        // const table = $("#resource-table");

        table.dataTable({
            responsive: true,
            ajax: window.location.href,
            // DOM Layout settings
            dom: `<'row'<'col-sm-12'tr>>
                    <'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'lp>>`,

            lengthMenu: [5, 10, 25, 50],

            pageLength: 10,

            language: {
                lengthMenu: "Display _MENU_"
            },

            // Order settings
            order: [[1, "desc"]],
            columns: [
                {
                    name: "name",
                    data: "name",
                    title: "Wrestler Name"
                },
                {
                    name: "hometown",
                    data: "hometown",
                    title: "Hometown"
                },
                {
                    name: "hometown",
                    data: "formatted_hired_at",
                    title: "Date Hired"
                },
                {
                    name: "status.name",
                    data: "status",
                    title: "Status"
                },
                {
                    name: "action",
                    data: "action",
                    title: "",
                    searchable: false,
                    orderable: false
                }
            ],
            columnDefs: [
                {
                    targets: -1,
                    title: "Actions",
                    orderable: false,
                    render: function(data, type, full, meta) {
                        return `
                                <span class="dropdown">
                                    <a href="#" class="btn btn-sm btn-clean btn-icon btn-icon-md" data-toggle="dropdown" aria-expanded="true">
                                    <i class="la la-ellipsis-h"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#"><i class="la la-edit"></i> Edit Details</a>
                                        <a class="dropdown-item" href="#"><i class="la la-leaf"></i> Update Status</a>
                                        <a class="dropdown-item" href="#"><i class="la la-print"></i> Generate Report</a>
                                    </div>
                                </span>
                                <a href="#" class="btn btn-sm btn-clean btn-icon btn-icon-md" title="View">
                                <i class="la la-edit"></i>
                                </a>`;
                    }
                }
            ]
        });
    };

    return {
        //main function to initiate the module
        init: function() {
            initTable();
        }
    };
})();

jQuery(document).ready(function() {
    ResourceBasicTable.init();
});
