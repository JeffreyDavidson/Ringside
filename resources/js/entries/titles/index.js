"use strict";

const table = $('[data-table="titles.index"]');
const rowCounter = $("#kt_subheader_total");
const searchInput = $("#generalSearch");
const statusDropdown = $("#status-dropdown");
const introducedAtStart = $("#introduced_at_start");
const introducedAtEnd = $("#introduced_at_end");

const filterData = {
    status: null,
    introduced_at: null
};

const updateFilters = () => {
    filterData.status = statusDropdown.val();
    if (introducedAtStart.val() && introducedAtEnd.val()) {
        filterData.introduced_at = [
            introducedAtStart.val(),
            introducedAtEnd.val()
        ];
    } else if (introducedAtStart.val()) {
        filterData.introduced_at = [introducedAtStart.val()];
    } else {
        filterData.introduced_at = null;
    }

    table
        .dataTable()
        .api()
        .draw();
};

const clearFilters = () => {
    filterData.status = null;
    filterData.introduced_at = null;
    statusDropdown.val("");
    introducedAtStart.val("");
    introducedAtEnd.val("");
    table
        .dataTable()
        .api()
        .draw();
};

// begin first table
table.DataTable({
    // Order settings
    order: [[0, "asc"]],
    ajax: {
        url: window.location.href,
        data(params) {
            params.status = filterData.status;
            params.introduced_at = filterData.introduced_at;
        }
    },
    columns: [
        { data: "id", title: "Title ID" },
        { data: "name", title: "Title" },
        { data: "introduced_at", title: "Date Introduced", searchable: false },
        {
            data: "status",
            title: "Status",
            searchable: false
        },
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

$("#applyFilters").click(() => updateFilters());
$("#clearFilters").click(() => clearFilters());
