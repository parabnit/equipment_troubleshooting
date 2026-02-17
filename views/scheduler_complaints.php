<?php
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/header.php");
include("../includes/common.php");
?>

<!-- CSS Libraries -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- ================= MAIN ================= -->
<main class="container-fluid py-3">

    <div class="row g-3">

        <!-- LEFT MENU -->
        <div class="col-lg-2 col-md-3">
            <?php include("../includes/menu.php"); ?>
        </div>

        <!-- CONTENT -->
        <div class="col-lg-10 col-md-9">

            <div class="card shadow-sm border-0 h-100">

                <!-- HEADER -->
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fa fa-calendar-check me-2"></i>
                        Scheduled Complaints
                    </h5>
                    <span class="small opacity-75">
                        Manage your equipment & task schedules
                    </span>
                </div>

                <!-- BODY -->
                <div class="card-body p-3">

                    <div class="table-responsive">
                        <table id="schedulerTable"
                               class="table table-hover table-striped table-bordered align-middle w-100">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="50">Sr No</th>
                                    <th width="120">Type</th>
                                    <th width="120">Complaint ID</th>
                                    <th>Member</th>
                                    <th width="120">Tool</th>
                                    <th width="100">Timer</th>
                                    <th>Description</th>
                                    <th>Allocated To</th>
                                    <th width="100">Status</th>
                                    <th width="90">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>
    </div>
</main>

<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    loadSchedulerTable();
});

function loadSchedulerTable() {

    $("#schedulerTable").DataTable({
        destroy: true,
        processing: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],

        ajax: {
            url: "fetch_scheduler_complaints_api.php",
            dataSrc: "data"
        },

        columns: [
            {
                data: null,
                className: "text-center",
                render: (data, type, row, meta) => meta.row + 1
            },
            {
                data: "team",
                render: val => `<span class="badge bg-primary">${val}</span>`
            },
            {
                data: "complaint_id",
                render: val => `<strong>#${val}</strong>`
            },
            {
                data: "member_name",
                render: val => `<strong>${val}</strong>`
            },
            {
                data: "tool_name",
                render: val => `<span class="badge bg-info">${val}</span>`
            },
            {
                data: "timer",
                className: "text-center",
                render: val => val
                    ? `<span class="badge bg-warning text-dark">${val}</span>`
                    : "-"
            },
            {
                data: "complaint_description",
                render: val => `
                    <div class="desc-text" title="${val}">
                        ${val}
                    </div>
                `
            },
            {
                data: "allocated_to_name",
                render: val => val || "-"
            },
            {
                data: "task_status",
                className: "text-center",
                render: val =>
                    val == 1
                        ? "<span class='badge bg-success'>Active</span>"
                        : "<span class='badge bg-secondary'>Stopped</span>"
            },
            {
                data: null,
                className: "text-center",
                render: data => {
                    if (data.task_status == 1) {
                        return `
                            <button class="btn btn-outline-danger btn-sm stopBtn"
                                    data-task-id="${data.task_id}"
                                    data-complaint-id="${data.complaint_id}"
                                    title="Stop Scheduler">
                                <i class="fa fa-stop"></i>
                            </button>
                        `;
                    }
                    return "-";
                }
            }
        ],

        createdRow: function (row, data) {
            if (data.task_status == 0) {
                $(row).addClass("table-secondary scheduler-inactive");
            }
        },

        drawCallback: function () {

            $("#schedulerTable")
            .off("click", ".stopBtn")
            .on("click", ".stopBtn", function () {

                let btn = $(this);
                let taskId = btn.data("task-id");
                let complaintId = btn.data("complaint-id");
                let row = btn.closest("tr");

                Swal.fire({
                    title: "Stop Scheduler?",
                    text: "This scheduler will be permanently stopped.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc3545",
                    confirmButtonText: "Yes, Stop"
                }).then((result) => {

                    if (!result.isConfirmed) return;

                    btn.prop("disabled", true);

                    $.post(
                        "stop_scheduler_api.php",
                        {
                            task_id: taskId,
                            complaint_id: complaintId
                        },
                        function (res) {

                            if (res.status === "success") {

                                row.addClass("table-secondary scheduler-inactive");
                                row.find("td:eq(7)").html(
                                    "<span class='badge bg-secondary'>Stopped</span>"
                                );
                                row.find("td:eq(8)").html("-");

                                Swal.fire({
                                    icon: "success",
                                    title: "Scheduler Stopped",
                                    timer: 1200,
                                    showConfirmButton: false
                                });

                            } else {
                                btn.prop("disabled", false);
                                Swal.fire("Error", res.message || "Failed", "error");
                            }
                        },
                        "json"
                    );
                });
            });
        }
    });
}
</script>

<!-- ================= STYLES ================= -->
<style>
.desc-text {
    max-width: 240px;
    font-size: 13px;
    line-height: 1.4;
    color: #333;
    white-space: normal;
    word-break: break-word;
}

.scheduler-inactive {
    opacity: 0.6;
    font-style: italic;
}

.stopBtn {
    width: 34px;
    height: 34px;
    border-radius: 50%;
}

.table th {
    white-space: nowrap;
}

#schedulerTable tbody tr:hover {
    background-color: #f4f6f9;
}
</style>

<?php include("../includes/footer.php"); ?>
