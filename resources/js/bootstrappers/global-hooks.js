flatpickr("[data-datetimepicker]", {
    enableTime: true,
    dateFormat: "Y-m-d H:i:00",
    altFormat: "Y-m-d H:i"
});

$("form.kt-nav").on("click", e => e.stopPropagation());
