<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentType = $_GET['type'] ?? '';
$currentStatus = $_GET['status'] ?? '';
$teamHeads = getTeamHead('all'); // all team heads
$isTeamHead = in_array($_SESSION['memberid'], $teamHeads);

$canViewTasksAndComplaints = (
    is_LabManager($_SESSION['memberid']) ||
    is_AssistLabManager($_SESSION['memberid']) ||
    is_EquipmentHead($_SESSION['memberid']) || is_EquipmentTeam($_SESSION['memberid']) ||
    is_FacilityHead($_SESSION['memberid'])  || is_FacilityTeam($_SESSION['memberid']) ||
    is_SafetyHead($_SESSION['memberid'])    || is_SafetyTeam($_SESSION['memberid']) ||
    is_ProcessHead($_SESSION['memberid'])   || is_ProcessTeam($_SESSION['memberid']) ||
    is_HRHead($_SESSION['memberid'])        || is_HRTeam($_SESSION['memberid']) ||
    is_ITHead($_SESSION['memberid'])        || is_ITTeam($_SESSION['memberid']) ||
    is_PurchaseHead($_SESSION['memberid'])  || is_PurchaseTeam($_SESSION['memberid']) ||
    is_TrainingHead($_SESSION['memberid'])  || is_TrainingTeam($_SESSION['memberid']) ||
    is_InventoryHead($_SESSION['memberid']) || is_InventoryTeam($_SESSION['memberid'])
);
?>

<style>
.mobile-menu-wrapper {
    display: flex;
    justify-content: center;
    margin: 12px 0 16px;
}

/* Mobile menu button */
.mobile-menu-btn {
    display: flex;
    align-items: center;
    gap: 10px;

    background: linear-gradient(135deg, #0ea5e9, #4f46e5);
    color: #fff;
    border: none;

    padding: 12px 22px;
    border-radius: 999px;

    font-size: 15px;
    font-weight: 600;

    min-width: 140px;
    justify-content: center;

    box-shadow: 0 10px 22px rgba(79,70,229,0.35);
    transition: all 0.25s ease;
}

.mobile-menu-btn i {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.mobile-menu-btn:active {
    transform: scale(0.96);
}

.mobile-menu-btn[aria-expanded="true"] i {
    transform: rotate(90deg);
}

@media (max-width: 360px) {
    .mobile-menu-btn {
        font-size: 14px;
        padding: 10px 18px;
        min-width: 120px;
    }
}

/* ================================
   SIDEBAR CONTAINER (GLASS LOOK)
================================ */

#sideMenu {
    background: linear-gradient(180deg, #f9fbff, #eef3ff);
    border-radius: 22px;
    padding: 16px 14px;
    box-shadow: 
        0 25px 50px rgba(79,70,229,0.15),
        inset 0 1px 0 rgba(255,255,255,0.6);
    border: 1px solid #e0e7ff;
}

/* ================================
   LOGGED-IN USER CARD (PREMIUM)
================================ */

#sideMenu .mb-3 {
    background: linear-gradient(135deg, #0f766e, #4338ca);
    color: #ffffff !important;
    padding: 16px;
    border-radius: 16px;
    font-size: 13px;
    box-shadow: 
        0 12px 28px rgba(67,56,202,0.45),
        inset 0 1px 0 rgba(255,255,255,0.2);
}

/* Force white text */
#sideMenu .mb-3,
#sideMenu .mb-3 strong,
#sideMenu .mb-3 small,
#sideMenu .mb-3 span {
    color: #ffffff !important;
}

/* Role badge */
.role-badge {
    background: rgba(255,255,255,0.22) !important;
    border: 1px solid rgba(255,255,255,0.35);
    color: #f1f5f9 !important;
    backdrop-filter: blur(6px);
}

/* ================================
   MENU ITEMS (SOFT GLASS CARDS)
================================ */

#sideMenu .list-group-item {
    position: relative;
    background: rgba(255,255,255,0.92);
    color: #0f172a;
    border: 1px solid #e0e7ff;
    border-radius: 14px;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    padding: 12px 14px 12px 16px;
    transition: all 0.25s ease;
    overflow: hidden;

    box-shadow:
        0 6px 14px rgba(79,70,229,0.08);
}

/* Left accent bar */
#sideMenu .list-group-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 12%;
    width: 4px;
    height: 76%;
    background: linear-gradient(180deg, #22d3ee, #6366f1);
    border-radius: 6px;
    opacity: 0;
    transition: all 0.25s ease;
}

/* Hover */
#sideMenu .list-group-item:hover {
    background: #f0f6ff;
    color: #0f172a;
    transform: translateX(4px);
    box-shadow:
        0 10px 24px rgba(99,102,241,0.25);
}

#sideMenu .list-group-item:hover::before {
    opacity: 1;
}

/* Active */
#sideMenu .list-group-item.active {
    background: linear-gradient(135deg, #e0f2fe, #eef2ff);
    color: #312e81;
    border-color: #a5b4fc;
    box-shadow:
        0 12px 26px rgba(99,102,241,0.35);
}

#sideMenu .list-group-item.active::before {
    opacity: 1;
}

/* ================================
   SECTION HEADERS
================================ */

#sideMenu .bg-light.fw-bold {
    background: linear-gradient(135deg, #e0f2fe, #e0e7ff) !important;
    color: #1e3a8a !important;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 14px;
    margin-top: 12px;
    margin-bottom: 8px;
    padding: 12px 14px;
    border: 1px solid #c7d2fe;
}

/* Sub items */
#sideMenu .ps-4 {
    padding-left: 36px !important;
    font-size: 13.5px;
    opacity: 0.95;
}

/* Collapse animation */
.collapse.show {
    animation: blueSlide 0.3s cubic-bezier(.4,0,.2,1);
}

@keyframes blueSlide {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile */
@media (max-width: 768px) {
    #sideMenu {
        border-radius: 18px;
        padding: 12px 10px;
    }
}
</style>



<!-- Mobile Menu Toggle Button -->
<div class="d-md-none mobile-menu-wrapper">
    <button class="mobile-menu-btn"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#sideMenu"
            aria-expanded="false"
            aria-controls="sideMenu">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
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
        <?php if ($canViewTasksAndComplaints): ?>
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
        <?php endif; ?>

        <?php if ($canViewTasksAndComplaints): ?>
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
    <?php endif; ?>

    <?php if ($canViewTasksAndComplaints): ?>
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
        <?php endif; ?>
        <?php if (is_Admin($_SESSION['memberid']) == 1 || is_ITadmin($_SESSION['memberid']) == 1 ||
            is_LabManager($_SESSION['memberid'])): ?>
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
