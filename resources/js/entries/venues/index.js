"use strict";
// Class definition

var KTAppVenueListDatatable = (function() {
    // variables
    var datatable;

    // init
    var init = function() {
        datatable = $("#kt_apps_venue_list_datatable").KTDatatable({
            // datasource definition
            data: {
                type: "remote",
                source: {
                    read: {
                        url: "/venues",
                        method: "GET",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                                "content"
                            )
                        }
                    }
                },
                pageSize: 10, // display 20 records per page
                serverPaging: true,
                serverFiltering: true,
                serverSorting: true
            },

            // layout definition
            layout: {
                scroll: false, // enable/disable datatable scroll both horizontal and vertical when needed.
                footer: false // display/hide footer
            },

            // column sorting
            sortable: true,

            pagination: true,

            search: {
                input: $("#generalSearch"),
                delay: 200
            },

            // columns definition
            columns: [
                {
                    field: "name",
                    title: "Name",
                    width: 200
                },
                {
                    field: "address",
                    title: "Address",
                    template: function(row) {
                        return row.address1.concat(
                            !row.address2 ? "" : " " + row.address2
                        );
                    }
                },
                {
                    field: "city",
                    title: "City"
                },
                {
                    field: "state",
                    title: "State"
                },
                {
                    field: "zip",
                    title: "Zip"
                },
                {
                    field: "action",
                    width: 80,
                    title: "Actions",
                    sortable: false,
                    autoHide: false,
                    overflow: "visible"
                }
            ]
        });
    };

    // search
    var search = function() {
        $("#generalSearch").on("change", function() {
            datatable.search(
                $(this)
                    .val()
                    .toLowerCase(),
                "name"
            );
        });
    };

    var updateTotal = function() {
        datatable.on("kt-datatable--on-layout-updated", function() {
            $("#kt_subheader_total").html(datatable.getTotalRows() + " Total");
        });
    };

    return {
        // public functions
        init: function() {
            init();
            search();
            updateTotal();
        }
    };
})();

// On document ready
KTUtil.ready(function() {
    KTAppVenueListDatatable.init();
});
