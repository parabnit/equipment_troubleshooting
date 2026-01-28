<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentType = $_GET['type'] ?? '';
$currentStatus = $_GET['status'] ?? '';
?>

<!-- Mobile Menu Toggle Button -->
<div class="d-md-none text-end mb-2">
    <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sideMenu" aria-expanded="false" aria-controls="sideMenu">
        <i class="fas fa-bars"></i> Menu
    </button>
</div>

<!-- Sidebar Menu -->
<div class="collapse d-md-block" id="sideMenu">

    <div class="mb-3 text-end text-muted small">
        Logged in as: <strong><?php echo getName($_SESSION['memberid']); ?></strong>
    </div>

    <div class="list-group">
<!-- Dashboard -->
        <a href="../views/dashboard.php" 
           class="list-group-item list-group-item-action <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            Dashboard
        </a>
        <a href="../views/complaint.php" class="list-group-item list-group-item-action">
            Complaint Form
        </a>

        <!-- My Complaints -->
        <a href="javascript:void(0)"
            class="list-group-item list-group-item-action bg-light fw-bold"
            data-bs-toggle="collapse"
            data-bs-target="#collapseMyComplaints"
            role="button"
            aria-expanded="false"
            aria-controls="collapseMyComplaints">
            My Complaints
        </a>

        <div class="collapse <?php echo ($currentPage == 'my_complaint.php') ? 'show' : ''; ?>" id="collapseMyComplaints">
            <?php
            $types = ['1' => 'Equipment', '2' => 'Facility', '3' => 'Safety', '4' => 'Process'];
            foreach ($types as $key => $label): ?>
                <a href="../views/my_complaint.php?type=<?php echo $key; ?>" class="list-group-item list-group-item-action ps-4"><?php echo $label; ?></a>
            <?php endforeach; ?>
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
            <?php foreach ($types as $key => $label): ?>
                <a href="../views/all_complaints.php?type=<?php echo $key; ?>&status=pending&importance=all" class="list-group-item list-group-item-action ps-4"><?php echo $label; ?></a>
            <?php endforeach; ?>
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
            <?php foreach ($types as $key => $label): ?>
                <a href="../views/all_complaints.php?type=<?php echo $key; ?>&status=closed&importance=all" class="list-group-item list-group-item-action ps-4"><?php echo $label; ?></a>
            <?php endforeach; ?>
        </div>

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

        <!-- Statistics -->
        <a class="list-group-item list-group-item-action bg-light fw-bold"
            href="javascript:void(0)"
            data-bs-toggle="collapse"
            data-bs-target="#collapseStats"
            role="button"
            aria-expanded="false"
            aria-controls="collapseStats">
            Statistics
        </a>

        <div class="collapse <?php echo ($currentPage == 'statistics.php') ? 'show' : ''; ?>" id="collapseStats">
            <a href="../views/statistics.php" class="list-group-item list-group-item-action ps-4">Dashboard</a>
        </div>

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

        <!-- Logout 
        <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
            Logout
        </a> -->

    </div>
</div>
