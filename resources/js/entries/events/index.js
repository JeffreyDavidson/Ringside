"use strict";

const table = $('[data-table="events.index"]');
const rowCounter = $("#kt_subheader_total");
const searchInput = $("#generalSearch");
const statusDropdown = $("#status-dropdown");
const dateStart = $("#date_start");
const dateEnd = $("#date_end");

const filterData = {
    status: null,
    date: null
};

// begin first table
table.DataTable({
    ajax: {
        url: window.location.href,
        data(params) {
            params.status = filterData.status;
            params.date = filterData.date;
        }
    },
    columns: [
        { data: "id", title: "Wrestler ID" },
        { data: "name", title: "Name" },
        { data: "date", title: "Date" },
        { data: "status", title: "Status", searchable: false },
        {
            data: "action",
            title: "Action",
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

$("#applyFilters").click(() => updateFilters());
$("#clearFilters").click(() => clearFilters());
