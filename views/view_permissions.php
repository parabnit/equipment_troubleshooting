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
/* ============================================
   🚀 ULTRA PREMIUM TEAM PERMISSION UI (2026)
   Pure CSS • No Layout Change • SaaS Level
   ============================================ */

/* Main Card */
.team-card {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(22px);
  border-radius: 28px;
  margin-bottom: 28px;
  overflow: hidden;
  position: relative;

  border: 1px solid rgba(255, 255, 255, 0.40);
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.14);

  transition: all 0.45s cubic-bezier(0.25, 1, 0.3, 1);
}

/* Animated Gradient Border Glow */
.team-card::before {
  content: "";
  position: absolute;
  inset: 0;
  padding: 2px;
  border-radius: 28px;

  background: linear-gradient(
    120deg,
    #3b82f6,
    #9333ea,
    #22c55e,
    #f97316
  );

  background-size: 300% 300%;
  animation: borderFlow 6s infinite linear;

  -webkit-mask: linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;

  opacity: 0.55;
  pointer-events: none;
}

/* Border Animation */
@keyframes borderFlow {
  0% {
    background-position: 0% 50%;
  }
  100% {
    background-position: 100% 50%;
  }
}

/* Hover Lift */
.team-card:hover {
  transform: translateY(-12px) scale(1.02);
  box-shadow: 0 35px 90px rgba(99, 102, 241, 0.35);
}

/* ===============================
   HEADER (Ultra Modern Gradient)
   =============================== */
.team-card-header {
  padding: 20px 24px;
  font-size: 19px;
  font-weight: 950;
  color: #fff;

  background: linear-gradient(
    135deg,
    rgba(59, 130, 246, 0.95),
    rgba(99, 102, 241, 0.95),
    rgba(147, 51, 234, 0.95)
  );

  display: flex;
  align-items: center;
  gap: 12px;

  position: relative;
  overflow: hidden;
}

/* Header Shine Sweep */
.team-card-header::after {
  content: "";
  position: absolute;
  top: 0;
  left: -120%;
  width: 120%;
  height: 100%;
  background: linear-gradient(
    120deg,
    transparent,
    rgba(255, 255, 255, 0.45),
    transparent
  );
  animation: shineMove 4s infinite;
}

@keyframes shineMove {
  0% {
    left: -120%;
  }
  100% {
    left: 120%;
  }
}

/* ===============================
   BODY CONTENT
   =============================== */
.team-card-body {
  padding: 24px;
  font-size: 14px;
  color: #111827;
  line-height: 1.85;
}

/* Section */
.team-section {
  margin-bottom: 20px;
}

/* Title */
.team-section-title {
  font-weight: 950;
  font-size: 14px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 10px;

  color: #1f2937;
}

/* ===============================
   PERMISSION PILLS (Neon Chips)
   =============================== */
.name-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  padding: 10px 18px;
  border-radius: 999px;

  background: rgba(99, 102, 241, 0.12);
  border: 1px solid rgba(99, 102, 241, 0.20);

  color: #4338ca;
  font-weight: 900;
  font-size: 13px;

  margin: 7px 9px 7px 0;
  cursor: pointer;

  transition: all 0.35s ease;
  position: relative;
}

/* Soft Pulse Glow */
.name-pill::after {
  content: "";
  position: absolute;
  inset: -6px;
  border-radius: 999px;
  background: rgba(147, 51, 234, 0.25);
  filter: blur(14px);
  opacity: 0;
  transition: 0.35s;
}

/* Hover Neon Effect */
.name-pill:hover {
  background: linear-gradient(135deg, #3b82f6, #9333ea);
  color: #fff;
  transform: translateY(-4px) scale(1.12);
  box-shadow: 0 18px 40px rgba(147, 51, 234, 0.30);
}

.name-pill:hover::after {
  opacity: 1;
}

/* ===============================
   ENTRY ANIMATION
   =============================== */
.team-card {
  animation: fadeSlide 0.8s ease both;
}

@keyframes fadeSlide {
  from {
    opacity: 0;
    transform: translateY(30px);
    filter: blur(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
    filter: blur(0);
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
                    <?php
                    $displayTeam = ($team === 'Lab') ? 'Lab Manager' : $team . ' Team';
                    ?>
                    <?= htmlspecialchars($displayTeam) ?>

                </div>

                <div class="team-card-body">
                    <div class="team-section">
                        <div class="team-section-title">
                            <i class="fa-solid fa-user-tie text-blue-600"></i>
                            Head
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
