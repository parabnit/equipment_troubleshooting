<?php 
include("../includes/auth_check.php");
include("../config/connect.php");
include("../includes/header.php");
include("../includes/common.php");
?>

<link rel="stylesheet" href="../assets/css/all.min.css">
<link rel="stylesheet" href="../assests/css/jquery.dataTables.min.css">

<main class="container py-4">
    <div class="row">

        <div class="col-md-3">
            <?php include("../includes/menu.php"); ?>
        </div>

        <div class="col-md-9">

            <!-- FORM CARD -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    Add Role
                </div>

                <div class="card-body">

                    <div id="msg" class="text-danger mb-3"></div>

                    <form id="roleForm">

                        <input type="hidden" id="role_id">

                        <div class="mb-3">
                            <label class="form-label">Role Name *</label>
                            <input type="text" class="form-control" id="role_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-success" id="saveBtn">Save Role</button>
                        <button type="button" class="btn btn-primary d-none" id="updateBtn">Update Role</button>

                    </form>

                </div>
            </div>

            <!-- DATATABLE -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Role List
                </div>

                <div class="card-body">
                    <table id="roleTable" class="display table table-bordered table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Role ID</th>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</main>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function () {

    loadRoleTable();

    // Save new role
    $("#roleForm").on("submit", function(e){
        e.preventDefault();

        let payload = {
            role: $("#role_name").val(),
            description: $("#description").val()
        };

        $.ajax({
            url: "save_role_api.php",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload),
            success: function(res){
                if(res.status === "success"){
                    $("#msg").html("<span class='text-success'>Role added successfully!</span>");
                    $("#roleForm")[0].reset();
                    loadRoleTable();
                } else {
                    $("#msg").html("<span class='text-danger'>" + res.message + "</span>");
                }
            }
        });

    });

    // Update role
    $("#updateBtn").on("click", function(){
        let payload = {
            role_id: $("#role_id").val(),
            role: $("#role_name").val(),
            description: $("#description").val()
        };

        $.ajax({
            url: "update_role_api.php",
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload),
            success: function(res){
                if(res.status === "success"){
                    $("#msg").html("<span class='text-success'>Role updated successfully!</span>");
                    $("#roleForm")[0].reset();
                    $("#saveBtn").removeClass("d-none");
                    $("#updateBtn").addClass("d-none");
                    loadRoleTable();
                } else {
                    $("#msg").html("<span class='text-danger'>" + res.message + "</span>");
                }
            }
        });
    });

});

// Load table
function loadRoleTable(){
    $("#roleTable").DataTable({
        destroy: true,
        ajax: {
            url: "fetch_roles_api.php",
            dataSrc: "data"
        },
        columns: [
            { data: "role_id" },
            { data: "role" },
            { data: "description" },
            {
                data: null,
                render: function(data){
                    return `<button class="btn btn-sm btn-warning editRoleBtn" 
                                    data-id="${data.role_id}" 
                                    data-role="${data.role}" 
                                    data-desc="${data.description}">
                                <i class="fa fa-edit"></i>
                            </button>`;
                }
            }
        ],
        drawCallback: function(){
            // attach click event for edit buttons
            $(".editRoleBtn").off().on("click", function(){
                let id = $(this).data("id");
                let role = $(this).data("role");
                let desc = $(this).data("desc");

                $("#role_id").val(id);
                $("#role_name").val(role);
                $("#description").val(desc);

                $("#saveBtn").addClass("d-none");
                $("#updateBtn").removeClass("d-none");
                $("#msg").html("");
            });
        }
    });
}
</script>

<?php include("../includes/footer.php"); ?>
