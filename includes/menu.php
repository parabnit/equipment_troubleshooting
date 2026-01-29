<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentType = $_GET['type'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$teamHeads = getTeamHead('all'); // all team heads
$isTeamHead = in_array($_SESSION['memberid'], $teamHeads);
?>
<!-- Mobile Menu Toggle Button -->
<div class="d-md-none text-end mb-2">
    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sideMenu" aria-expanded="false" aria-controls="sideMenu">
        <i class="fas fa-bars"></i> Menu
    </button>
</div>

<!-- Sidebar Menu -->
<!-- Sidebar Menu -->
<div class="collapse d-md-block" id="sideMenu">

    <div class="mb-3 text-end text-muted small">
        Logged in as:
        <strong><?= getName($_SESSION['memberid']); ?></strong>

        <?php
            $heads = getUserHeadLabels($_SESSION['memberid']);
            if (!empty($heads)):
                if (count($heads) > 2) {
                    // More than 2 roles: show first 2 and "and more"
                    $displayText = implode(', ', array_slice($heads, 0, 2)) . ' and more';
                } else {
                    // 1 or 2 roles: show all names
                    $displayText = implode(', ', $heads);
                }
            ?>
                <span class="badge bg-primary ms-2 role-badge">
                    <?= $displayText; ?>
                </span>
            <?php endif; ?>
</div>

    <div class="list-group">
<!-- Dashboard -->
  <?php
                 if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_EquipmentHead($_SESSION['memberid']) || is_EquipmentTeam($_SESSION['memberid']) || is_FacilityHead($_SESSION['memberid']) || is_FacilityTeam($_SESSION['memberid']) || is_SafetyHead($_SESSION['memberid']) || is_SafetyTeam($_SESSION['memberid']) || is_ProcessHead($_SESSION['memberid']) || is_ProcessTeam($_SESSION['memberid']) || is_HRHead($_SESSION['memberid']) || is_HRTeam($_SESSION['memberid']) || is_ITHead($_SESSION['memberid']) || is_ITTeam($_SESSION['memberid']) || is_PurchaseHead($_SESSION['memberid']) || is_PurchaseTeam($_SESSION['memberid']) || is_TrainingHead($_SESSION['memberid']) || is_TrainingTeam($_SESSION['memberid']) || is_InventoryHead($_SESSION['memberid']) || is_InventoryTeam($_SESSION['memberid']) ){
                   ?>
        <a href="../views/dashboard.php" 
           class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            Dashboard
        </a>
        <?php } ?>
        <a href="../views/complaint.php" class="list-group-item list-group-item-action">
            Complaint Form
        </a>

        <!-- My Complaints -->
        <a href="../views/my_complaint.php"
        class="list-group-item list-group-item-action <?php echo ($currentPage == 'my_complaint.php') ? 'active' : ''; ?>">
            My Complaints
        </a>

        <!-- Daily Tasks -->
              <a class="list-group-item list-group-item-action bg-light fw-bold"
            href="javascript:void(0)"
            data-bs-toggle="collapse"
            data-bs-target="#collapseDailyTasks"
            role="button"
            aria-expanded="<?= in_array($currentPage, ['daily_tasks.php']) ? 'true' : 'false' ?>"
            aria-controls="collapseDailyTasks">
                Daily Tasks
            </a>

            <div class="collapse <?= ($currentPage == 'daily_tasks.php') ? 'show' : ''; ?>"
                id="collapseDailyTasks">

                <?php
                 if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_EquipmentHead($_SESSION['memberid']) || is_EquipmentTeam($_SESSION['memberid']) ){
                   ?>
                    <a href="../views/daily_tasks.php?type=1"
                    class="list-group-item list-group-item-action ps-4">
                    Equipment
                    </a>
                <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_FacilityHead($_SESSION['memberid']) || is_FacilityTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=2"
                    class="list-group-item list-group-item-action ps-4">
                    Facility
                    </a>

                    <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_SafetyHead($_SESSION['memberid']) || is_SafetyTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=3"
                    class="list-group-item list-group-item-action ps-4">
                    Safety
                    </a>
                    <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ProcessHead($_SESSION['memberid']) || is_ProcessTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=4"
                    class="list-group-item list-group-item-action ps-4">
                    Process
                    </a>
                    <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_HRHead($_SESSION['memberid']) || is_HRTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=5"
                    class="list-group-item list-group-item-action ps-4">
                    HR
                    </a>
                    <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ITHead($_SESSION['memberid']) || is_ITTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=6"
                    class="list-group-item list-group-item-action ps-4">
                    IT
                    </a>
                    <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_PurchaseHead($_SESSION['memberid']) || is_PurchaseTeam($_SESSION['memberid']) ){?>
                    <a href="../views/daily_tasks.php?type=7"
                    class="list-group-item list-group-item-action ps-4">
                    Purchase
                    </a>
                    <?php } ?>

                    <!-- Added Training -->
                    <?php if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_TrainingHead($_SESSION['memberid']) || is_TrainingTeam($_SESSION['memberid']) ){ ?>
                        <a href="../views/daily_tasks.php?type=8"
                        class="list-group-item list-group-item-action ps-4">
                            Training
                        </a>
                    <?php } ?>

                    <!-- Added Inventory -->
                    <?php if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_InventoryHead($_SESSION['memberid']) || is_InventoryTeam($_SESSION['memberid']) ){ ?>
                        <a href="../views/daily_tasks.php?type=9"
                        class="list-group-item list-group-item-action ps-4">
                            Inventory
                        </a>
                    <?php } ?>

                        
            </div>



        <!-- Pending Complaints -->
        <a class="list-group-item list-group-item-action bg-light fw-bold"
            href="javascript:void(0)"
            data-bs-toggle="collapse"
            data-bs-target="#collapsePending"
            role="button"
            aria-expanded="false"
            aria-controls="collapsePending">
            Pending Complaints
        </a>

        <div class="collapse <?php echo ($currentPage == 'all_complaints.php' && $currentStatus == 'pending') ? 'show' : ''; ?>" id="collapsePending">
        <?php
                 if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_EquipmentHead($_SESSION['memberid']) || is_EquipmentTeam($_SESSION['memberid']) ){
                   ?>
                <a href="../views/all_complaints.php?type=1&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Equipment</a>

                <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_FacilityHead($_SESSION['memberid']) || is_FacilityTeam($_SESSION['memberid']) ){?>

                <a href="../views/all_complaints.php?type=2&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Facility</a>

                    <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_SafetyHead($_SESSION['memberid']) || is_SafetyTeam($_SESSION['memberid']) ){?>

                <a href="../views/all_complaints.php?type=3&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Safety</a>

                        <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ProcessHead($_SESSION['memberid']) || is_ProcessTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/all_complaints.php?type=4&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Process</a>

                            <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_HRHead($_SESSION['memberid']) || is_HRTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/all_complaints.php?type=5&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">HR</a>

                            <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ITHead($_SESSION['memberid']) || is_ITTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/all_complaints.php?type=6&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">IT</a>

                            <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_PurchaseHead($_SESSION['memberid']) || is_PurchaseTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/all_complaints.php?type=7&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Purchase</a>

                            <?php } ?>

                             <?php
                // Training
                if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_TrainingHead($_SESSION['memberid']) || is_TrainingTeam($_SESSION['memberid']) ){
                ?>
                    <a href="../views/all_complaints.php?type=8&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Training</a>
                <?php } ?>

                <?php
                // Inventory
                if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_InventoryHead($_SESSION['memberid']) || is_InventoryTeam($_SESSION['memberid']) ){
                ?>
                    <a href="../views/all_complaints.php?type=9&status=pending&importance=all" class="list-group-item list-group-item-action ps-4">Inventory</a>
                <?php } ?>

                        
        </div>

        <!-- Closed Complaints -->
        <a class="list-group-item list-group-item-action bg-light fw-bold"
            href="javascript:void(0)"
            data-bs-toggle="collapse"
            data-bs-target="#collapseClosed"
            role="button"
            aria-expanded="false"
            aria-controls="collapseClosed">
            Closed Complaints
        </a>

        <div class="collapse <?php echo ($currentPage == 'all_complaints.php' && $currentStatus == 'closed') ? 'show' : ''; ?>" id="collapseClosed">
        <?php
                 if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_EquipmentHead($_SESSION['memberid']) || is_EquipmentTeam($_SESSION['memberid']) ){
                   ?>
                <a href="../views/closed_complaints.php?type=1&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">Equipment</a>
                <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_FacilityHead($_SESSION['memberid']) || is_FacilityTeam($_SESSION['memberid']) ){?>
                <a href="../views/closed_complaints.php?type=2&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">Facility</a>
                  
                    <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_SafetyHead($_SESSION['memberid']) || is_SafetyTeam($_SESSION['memberid']) ){?>
                <a href="../views/closed_complaints.php?type=3&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">Safety</a>
                           
                        <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ProcessHead($_SESSION['memberid']) || is_ProcessTeam($_SESSION['memberid']) ){?>
                <a href="../views/closed_complaints.php?type=4&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">Process</a>
                               
                            <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_HRHead($_SESSION['memberid']) || is_HRTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/closed_complaints.php?type=5&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">HR</a>

                            <?php } if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_ITHead($_SESSION['memberid']) || is_ITTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/closed_complaints.php?type=6&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">IT</a>

                            <?php }  if(is_LabManager($_SESSION['memberid']) || is_AssistLabManager($_SESSION['memberid']) || is_PurchaseHead($_SESSION['memberid']) || is_PurchaseTeam($_SESSION['memberid']) ){?>
 
                <a href="../views/closed_complaints.php?type=7&status=closed&importance=all" class="list-group-item list-group-item-action ps-4">Purchase</a>

                            <?php } ?>

                <?php if (
                    is_LabManager($_SESSION['memberid']) ||
                    is_AssistLabManager($_SESSION['memberid']) ||
                    is_TrainingHead($_SESSION['memberid']) ||
                    is_TrainingTeam($_SESSION['memberid'])
                ) { ?>
                    <a href="../views/closed_complaints.php?type=8&status=closed&importance=all"
                    class="list-group-item list-group-item-action ps-4">
                        Training
                    </a>
                <?php } ?>

                <?php if (
                    is_LabManager($_SESSION['memberid']) ||
                    is_AssistLabManager($_SESSION['memberid']) ||
                    is_InventoryHead($_SESSION['memberid']) ||
                    is_InventoryTeam($_SESSION['memberid'])
                ) { ?>
                    <a href="../views/closed_complaints.php?type=9&status=closed&importance=all"
                    class="list-group-item list-group-item-action ps-4">
                        Inventory
                    </a>
                <?php } ?>
                     

        </div>
<?php if (is_Admin($_SESSION['memberid']) == 1 || is_ITadmin($_SESSION['memberid']) == 1): ?>
        <!-- Periodic Checks -->
        <a href="../views/periodic_checks.php" class="list-group-item list-group-item-action">
            Periodic Checks
        </a>

        <!-- Tool Down Duration -->
        <a class="list-group-item list-group-item-action bg-light fw-bold"
            href="javascript:void(0)"
            data-bs-toggle="collapse"
            data-bs-target="#collapseToolDown"
            role="button"
            aria-expanded="false"
            aria-controls="collapseToolDown">
            Tool Down Duration
        </a>

        <div class="collapse <?php echo ($currentPage == 'tool_down_duration.php') ? 'show' : ''; ?>" id="collapseToolDown">
            <?php foreach (['1' => 'Equipment', '2' => 'Facility', '3' => 'Safety'] as $key => $label): ?>
                <a href="../views/tool_down_duration.php?type=<?php echo $key; ?>" class="list-group-item list-group-item-action ps-4"><?php echo $label; ?></a>
            <?php endforeach; ?>
        </div>
 <?php endif; ?>
        <!-- Statistics -->
            <a href="../views/statistics.php"
            class="list-group-item list-group-item-action <?php echo ($currentPage == 'statistics.php') ? 'active' : ''; ?>">
            Statistics
            </a>


        <!-- Help -->
        <a href="../assets/efp_troubleshooting_help.pdf" class="list-group-item list-group-item-action" target="_blank">
            Help
        </a>

        <!-- Permission (admin) -->
        <?php if ($_SESSION['role'] == 1 || $_SESSION['role_ITadmin'] == 1): ?>
            <a href="../views/permission.php" class="list-group-item list-group-item-action">
                Permission
            </a>
        <?php endif; ?>
	 <!-- added by sowjanya on 21/01/2026 -->
        <a href="../views/view_permissions.php" class="list-group-item list-group-item-action">
            View Permissions
        </a>
<?php if ($isTeamHead): ?>
                <a href="../views/scheduler_complaints.php" class="list-group-item list-group-item-action">
                  Stop Scheduler
                </a>
            <?php endif; ?>

        <!-- Logout 
        <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
            Logout
        </a> -->

    </div>
</div>
