"use strict";

const renderStatusCell = (data, type, full, meta) =>
    data ? "Active" : "Inactive";

const table = $('[data-table="titles.index"]');
const rowCounter = $("#kt_subheader_total");
const searchInput = $("#generalSearch");

// begin first table
table.DataTable({
    // Order settings
    order: [[1, "desc"]],
    ajax: "/titles",
    columns: [
        { data: "id", title: "Title ID" },
        { data: "name", title: "Title" },
        { data: "slug", title: "Slug" },
        { data: "introduced_at", title: "Date Introduced" },
        { data: "is_active", title: "Status", render: renderStatusCell },
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
