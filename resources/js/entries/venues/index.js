("use strict");

const renderAddressCell = (data, type, full, meta) => {
    return full.address1.concat(!full.address2 ? "" : " " + full.address2);
};

const table = $('[data-table="venues.index"]');
const rowCounter = $("#kt_subheader_total");
const searchInput = $("#generalSearch");

// begin first table
table.DataTable({
    // Order settings
    order: [[0, "asc"]],
    ajax: "/venues",
    columns: [
        { data: "id", title: "Venue ID" },
        { data: "name", title: "Name" },
        { data: "address", title: "Address", render: renderAddressCell },
        { data: "city", title: "City" },
        { data: "state", title: "State" },
        { data: "zip", title: "Zip" },
        {
            data: "action",
            title: "Actions",
            orderable: false,
            responsivePriority: -1
        }
    ],
    initComplete(settings) {
        rowCounter.html(`${settings.fnRecordsTotal()} Total`);
    }
});

searchInput.on("keyup", () =>
    table
        .DataTable()
        .search(searchInput.val())
        .draw()
);

table.on("draw.dt", (e, settings) => {
    const searchTerm = table.DataTable().search();
    if (!searchTerm) {
        rowCounter.html(`${settings.fnRecordsTotal()} Total`);
    } else {
        rowCounter.html(`${settings.fnRecordsDisplay()} Matching Rows`);
    }
});
