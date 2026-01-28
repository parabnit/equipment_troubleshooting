<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

/* Fetch role master */
$role_master_res = mysqli_query($db_equip, "SELECT * FROM role_master ORDER BY role_id ASC");
$role_master = [];
while ($r = mysqli_fetch_assoc($role_master_res)) {
    $role_master[$r['role_id']] = $r['role'];
}

/* Fetch role allocation table */
$role_res = mysqli_query($db_equip, "SELECT memberid, role FROM role");

$teams = [];

while ($row = mysqli_fetch_assoc($role_res)) {
    $memberid = $row['memberid'];
    $role_id  = $row['role'];
    $role_name = $role_master[$role_id] ?? "";

    $member_name = getName($memberid);
    if (!$role_name || !$member_name) continue;

    $parts = explode(" ", $role_name);
    $team = $parts[0];

    if (!isset($teams[$team])) {
        $teams[$team] = [
            "heads" => [],
            "members" => []
        ];
    }

    if (stripos($role_name, "Head") !== false) {
        $teams[$team]["heads"][] = $member_name;
    } elseif (stripos($role_name, "Team") !== false) {
        $teams[$team]["members"][] = $member_name;
    } else {
        $teams[$team]["heads"][] = $member_name;
    }
}

ksort($teams);
foreach ($teams as $t => $vals) {
    sort($teams[$t]["heads"]);
    sort($teams[$t]["members"]);
}
?>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

<style>
.team-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    margin-bottom: 22px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transition: all 0.35s ease;
    animation: fadeUp 0.6s ease both;
}
.team-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(37,99,235,0.25);
}
.team-card-header {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: #fff;
    padding: 14px 18px;
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.team-card-body {
    padding: 18px;
    font-size: 14px;
}
.team-section {
    margin-bottom: 14px;
}
.team-section-title {
    font-weight: 700;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #111827;
}
.name-pill {
    display: inline-block;
    background: #eff6ff;
    color: #1e3a8a;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 13px;
    margin: 4px 6px 4px 0;
    transition: all 0.25s ease;
}
.name-pill:hover {
    background: #2563eb;
    color: #fff;
    transform: scale(1.05);
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<main class="container py-4">
<div class="row">
    <div class="col-md-3">
        <?php include("../includes/menu.php"); ?>
    </div>

    <div class="col-md-9">
        <h4 class="mb-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-shield-halved text-primary"></i>
            View Permissions
        </h4>

        <?php foreach ($teams as $team => $grp): ?>
            <div class="team-card">
                <div class="team-card-header">
                    <i class="fa-solid fa-users"></i>
                    <?= htmlspecialchars($team) ?> Team
                </div>

                <div class="team-card-body">
                    <div class="team-section">
                        <div class="team-section-title">
                            <i class="fa-solid fa-user-tie text-blue-600"></i>
                            Heads
                        </div>
                        <?php if (empty($grp['heads'])): ?>
                            <span class="text-muted">None</span>
                        <?php else: ?>
                            <?php foreach ($grp['heads'] as $h): ?>
                                <span class="name-pill"><?= htmlspecialchars($h) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="team-section">
                        <div class="team-section-title">
                            <i class="fa-solid fa-user-group text-green-600"></i>
                            Members
                        </div>
                        <?php if (empty($grp['members'])): ?>
                            <span class="text-muted">None</span>
                        <?php else: ?>
                            <?php foreach ($grp['members'] as $m): ?>
                                <span class="name-pill"><?= htmlspecialchars($m) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>
</main>

<?php include("../includes/footer.php"); ?>
