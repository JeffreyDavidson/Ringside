"use strict";

const renderActionCell = (data, type, full, meta) => `
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
  </a>
`;

const renderStatusCell = (data, type, full, meta) => {
    const status = {
        1: { title: "Pending", class: "kt-badge--brand" },
        2: { title: "Delivered", class: " kt-badge--danger" },
        3: { title: "Canceled", class: " kt-badge--primary" },
        4: { title: "Success", class: " kt-badge--success" },
        5: { title: "Info", class: " kt-badge--info" },
        6: { title: "Danger", class: " kt-badge--danger" },
        7: { title: "Warning", class: " kt-badge--warning" }
    };
    if (typeof status[data] === "undefined") {
        return data;
    }
    return `<span class="kt-badge ${
        status[data].class
    } kt-badge--inline kt-badge--pill">${status[data].title}</span>`;
};

const renderTypeCell = (data, type, full, meta) => {
    const status = {
        1: { title: "Online", state: "danger" },
        2: { title: "Retail", state: "primary" },
        3: { title: "Direct", state: "success" }
    };
    if (typeof status[data] === "undefined") {
        return data;
    }
    return `
    <span class="kt-badge kt-badge--${
        status[data].state
    } kt-badge--dot"></span>&nbsp;
    <span class="kt-font-bold kt-font-${status[data].state}">${
        status[data].title
    }</span>
  `;
};

var table = $('[data-table="wrestlers.index"]');

// begin first table
table.DataTable({
    responsive: true,
    searchDelay: 500,
    processing: true,
    serverSide: true,
    ajax:
        "https://keenthemes.com/metronic/themes/themes/metronic/dist/preview/inc/api/datatables/demos/server.php",
    columns: [
        { data: "OrderID", title: "Order ID" },
        { data: "Country", title: "Country" },
        { data: "ShipCity", title: "Shipping From" },
        { data: "CompanyName", title: "Company" },
        { data: "ShipDate", title: "Departure Date" },
        { data: "Status", title: "Status", render: renderStatusCell },
        { data: "Type", title: "Type", render: renderTypeCell },
        {
            data: "Actions",
            title: "Actions",
            render: renderActionCell,
            orderable: false,
            responsivePriority: -1
        }
    ]
});
