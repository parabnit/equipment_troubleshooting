<?php
include("../includes/auth_check.php");
include("../includes/header.php");
require_once("../config/connect.php");
require_once("../includes/common.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['login']) || $_SESSION['login'] == '') {
    header("location:logout.php");
}


// types, 
$type_equipment = 1;
$type_facility = 2;
$type_safety = 3;
$type_process = 4;

// function to get device name according to type={1,2,3,4}
function getToolNameByType($machine_id, $type)
{
    if ($type == 1 || $type == 4) {
        return getToolName($machine_id);
    } elseif ($type == 2) {
        return getToolName_facility($machine_id);
    } else {
        return getToolName_safety($machine_id);
    }
}

// Function to get maximum complaint of the type={1,2,3,4}
function getMaxComplaintsData($type)
{
    $max = 0;
    $result = [];
    $all_complaints = AllTool_in_complaint($type);

    if (empty($all_complaints)) {
        return [];
    }

    foreach ($all_complaints as $tool) {
        $count = count(Max_No_of_complaints($tool['machine_id'], $type));

        if ($count >= $max) {
            if ($count > $max) {
                $max = $count;
                $result = []; // Clear previous results for new max
            }
            $result[] = [
                'count' => $max,
                // 'name' => getToolName($tool['machine_id']),
                'name' => getToolNameByType($tool['machine_id'], $type)

            ];
        }
    }
    return $result;
}

// function to get complaints status of types{1,2,3,4} 
function getComplaintStats($type)
{
    $stats = [
        'total' => 0,
        'pending' => 0,
        'inprocess' => 0,
        'closed' => 0,
        'onhold' => 0
    ];

    $complaints = complaint('', $type, false);

    foreach ($complaints as $complaint) {
        $stats['total']++;
        switch ($complaint['status']) {
            case 0:
                $stats['pending']++;
                break;
            case 1:
                $stats['inprocess']++;
                break;
            case 2:
                $stats['closed']++;
                break;
            case 3:
                $stats['onhold']++;
                break;
        }
    }
    return $stats;
}

// function to get complaints status labwise
function getLabWiseComplaints($type)
{
    $lab_list = [];
    $complaints = complaint('', $type, false);


    foreach ($complaints as $complaint) {
        $lab_location = locationByType($complaint['machine_id'], $type);

        // Initialize lab entry if not exists
        if (!isset($lab_list[$lab_location])) {
            $lab_list[$lab_location] = [
                'pending' => 0,
                'inprocess' => 0,
                'closed' => 0,
                'onhold' => 0,
                'total' => 0,
                'lab_location' => $lab_location
            ];
        }

        // Update counts based on status
        switch ($complaint['status']) {
            case 0:
                $lab_list[$lab_location]['pending']++;
                break;
            case 1:
                $lab_list[$lab_location]['inprocess']++;
                break;
            case 2:
                $lab_list[$lab_location]['closed']++;
                break;
            case 3:
                $lab_list[$lab_location]['onhold']++;
                break;
        }
        $lab_list[$lab_location]['total']++;
    }

    // Filter labs with active complaints
    $filtered_labs = array_filter($lab_list, function ($lab) {
        return ($lab['pending'] > 0 || $lab['inprocess'] > 0 || $lab['onhold'] > 0);
    });

    // Convert to indexed array and sort by total complaints (descending)
    $sorted_labs = array_values($filtered_labs);
    usort($sorted_labs, function ($a, $b) {
        return $b['total'] - $a['total'];
    });

    return $sorted_labs;
}

// function to get data for resolution chart
function getToolResolutionData($type)
{
    $complaints_tool = AllTool_in_complaint($type);
    $complaints_tool_data = [];
    $complaints_less_than_100 = [];
    $complaints_more_than_100 = [];

    // echo "<script>console.log('$type:' ," . json_encode($$type) . ")</script>";
    // echo "<script>console.log('complaints:' ," . json_encode($complaints) . ")</script>";



    foreach ($complaints_tool as $t) {
        // $type = $type_equipment;
        $details = Max_No_of_complaints($t['machine_id'], $type);
        $resolved = array_filter($details, function ($d) {
            return $d['status'] == 2; // closed complaints
        });

        if (count($resolved) > 0) {
            $sum = 0;
            foreach ($resolved as $r) {
                $sum += count_day($r['time_of_complaint'], $r['status_timestamp']);
            }

            $avg = round($sum / count($resolved));
            $tool_data = [
                'name' => getToolNameByType($t['machine_id'], $type),
                'avg' => $avg,
                'sum_of_days' => $sum,
                'total_complaints' => count($resolved)
            ];

            $complaints_tool_data[] = $tool_data;

            if ($avg < 100) {
                $complaints_less_than_100[] = $tool_data;
            } else {
                $complaints_more_than_100[] = $tool_data;
            }
        }
    }

    // Sort data
    usort($complaints_tool_data, function ($a, $b) {
        return $b['avg'] - $a['avg'];
    });

    usort($complaints_less_than_100, function ($a, $b) {
        return $b['avg'] - $a['avg'];
    });

    usort($complaints_more_than_100, function ($a, $b) {
        return $b['avg'] - $a['avg'];
    });

    return [
        'all_data' => $complaints_tool_data,
        'less_than_100' => $complaints_less_than_100,
        'more_than_100' => $complaints_more_than_100
    ];
}

// function to get numbers of devices present in facility and Safety
function getDeviceNames($type)
{
    // global $link3, $link2;
    global $db_facility, $db_safety;

    // Define device checklists for each type
    $checkForDevices = ($type == 2)
        ? ['Dehumidifier', 'Chiller', 'ODU', 'AHU', 'AC', 'Blower']  // Facility devices
        : ['FE', 'GLD', 'Fire Panel', 'Breathing Apparatus', 'Smoke Detector', 'Shower']; // Safety devices

    // Initialize counts with our known devices plus 'Others'
    $deviceTypeCounts = array_fill_keys($checkForDevices, 0);
    $deviceTypeCounts['Others'] = 0;

    // Determine database connection and table details
    $conn = ($type == 2) ? $db_facility : $db_safety;
    $table = ($type == 2) ? 'resources' : 'safety_device';
    $column = ($type == 2) ? 'name' : 'device_name';

    if ($stmt = $conn->prepare("SELECT $column FROM $table")) {
        $stmt->execute();
        $name = '';
        $stmt->bind_result($name);

        while ($stmt->fetch()) {
            $matched = false;

            // Check for device types
            foreach ($checkForDevices as $keyword) {
                if (stripos($name, $keyword) !== false) {
                    $deviceTypeCounts[$keyword]++;
                    $matched = true;
                    break; // Stop checking other keywords once found
                }
            }

            // If no keywords matched, count as 'Others'
            if (!$matched) {
                $deviceTypeCounts['Others']++;
            }
        }
        $stmt->close();
    }

    return ['deviceTypeCounts' => $deviceTypeCounts];
}
?>
<style>
    /* Updated CSS with responsive improvements */
    .dashboard-header {
        background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    @media (min-width: 768px) {
        .dashboard-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .dashboard-header h1 {
        margin: 0 0 10px 0;
        font-size: 24px;
        display: flex;
        align-items: center;
    }

    .dashboard-header h1 i {
        margin-right: 10px;
    }

    .user-info {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    @media (min-width: 992px) {
        .dashboard-container {
            flex-direction: row;
        }
    }

    .sidebar {
        width: 100%;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    @media (min-width: 992px) {
        .sidebar {
            width: 250px;
            min-width: 250px;
        }
    }

    .main-content {
        flex: 1;
        width: 100%;
    }

    .stats-section {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .stats-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        width: 100%;
    }

    .stats-card.highlight {
        border-left: 4px solid #3a7bd5;
        background: #f8fafc;
    }

    .stats-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #3a7bd5;
        display: flex;
        align-items: center;
    }

    .stats-card h3 i {
        margin-right: 10px;
        font-size: 18px;
    }

    .stats-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .stat-item {
        background: #f1f8ff;
        padding: 8px 15px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        font-size: 14px;
    }

    .stat-label {
        font-weight: 500;
        margin-right: 5px;
    }

    .stat-value {
        font-weight: bold;
        color: #3a7bd5;
    }

    .stat-chart {
        width: 100%;
        min-height: 250px;
        margin: 0 auto 15px;
    }

    @media (min-width: 576px) {
        .stat-chart {
            min-height: 300px;
        }
    }

    .stats-details {
        /* display: grid; */
        display: flex;
        /* grid-template-columns: repeat(2, 1fr); */
        gap: 10px;
        align-content: center;
        justify-content: center;
    }

    @media (min-width: 768px) {
        .stats-details {
            grid-template-columns: repeat(5, 1fr);
        }
    }

    .stats-details .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
    }

    .stats-details .stat-label {
        font-size: 12px;
        margin-bottom: 5px;
    }

    .stats-details .stat-value {
        font-size: 18px;
    }

    .total {
        background: #e3f2fd;
    }

    .pending {
        background: #ffebee;
        color: #f44336;
    }

    .inprocess {
        background: #fff8e1;
        color: #ff9800;
    }

    .closed {
        background: #e8f5e9;
        color: #4caf50;
    }

    .onhold {
        background: #f3e5f5;
        color: #9c27b0;
    }

    .lab-stats {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .lab-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .lab-item:last-child {
        border-bottom: none;
    }

    .lab-item h4 {
        margin: 0 0 8px 0;
        font-size: 14px;
    }

    .progress-bar {
        height: 10px;
        background: #f5f5f5;
        border-radius: 5px;
        overflow: hidden;
        display: flex;
        margin-bottom: 5px;
    }

    .progress {
        height: 100%;
    }

    .progress.pending {
        background: #f44336;
    }

    .progress.inprocess {
        background: #ff9800;
    }

    .progress.closed {
        background: #4caf50;
    }

    .progress.onhold {
        background: #9c27b0;
    }

    .lab-details {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: #666;
    }

    .lab-details span {
        display: inline-block;
    }

    .lab-details .pending {
        color: #f44336;
    }

    .lab-details .inprocess {
        color: #ff9800;
    }

    .lab-details .closed {
        color: #4caf50;
    }

    .lab-details .onhold {
        color: #9c27b0;
    }

    .tool-resolution-chart {
        width: 100%;
        min-height: 400px;
        margin-top: 20px;
    }

    /* Animation for cards */
    .stats-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* New styles for side-by-side layout */
    .side-by-side-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    @media (min-width: 1200px) {
        .side-by-side-container {
            flex-direction: row;
        }

        .side-by-side-container>div {
            flex: 1;
        }
    }

    .resolution-charts-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    @media (min-width: 992px) {
        .resolution-charts-container {
            flex-direction: row;
        }

        .resolution-charts-container>div {
            flex: 1;
        }
    }

    .chart-container {
        position: relative;
        height: 400px;
        width: 100%;
    }

    .download-btn {
        display: inline-block;
        margin: 10px auto 0;
        padding: 6px 12px;
        font-size: 14px;
        background-color: #3a7bd5;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        text-align: center;
        /* Center using block-level margin */
        display: block;
    }

    .download-btn:hover {
        background-color: #2e6cc3;
    }

    .fullscreen-icon {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 18px;
        color: #444;
        cursor: pointer;
        z-index: 10;
    }

    .chart-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        padding-top: 60px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.8);
    }

    .chart-modal-content {
        background-color: #fff;
        margin: auto;
        padding: 20px;
        width: 90%;
        max-width: 1000px;
        position: relative;
        border-radius: 10px;
    }

    .chart-modal-content canvas {
        width: 100% !important;
        height: 700px !important;
    }

    .close-modal {
        color: #aaa;
        position: absolute;
        right: 20px;
        top: 10px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
</style>

<main class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <?php include("../includes/menu.php"); ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="stats-section">

                    <!-- Equipment :- higest complaint tool -->
                    <div class="stats-card highlight">
                        <?php $maxComplaints_equipment = getMaxComplaintsData($type_equipment);
                        // echo "<script>console.log('maxComplaints:', " . json_encode($maxComplaints_equipment) . ")</script>";
                        ?>
                        <h3><i class="fas fa-bolt"></i> Equipment with maximum complaints.</h3>
                        <div class="stats-list">
                            <?php foreach ($maxComplaints_equipment as $item): ?>
                                <div class="stat-item">
                                    <span class="stat-label"><?= $item['name'] ?></span>
                                    <span class="stat-value">
                                        <?= $item['count'] . ($item['count'] == 1 ? ' complaint' : ' complaints') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Side by side container for Equipment Status and Lab-wise Complaints for type Equipment Complaints -->
                    <div class="side-by-side-container">
                        <div class="stats-card">
                            <?php
                            // Get equipment complaint statistics
                            $stats_equipment = getComplaintStats($type_equipment);
                            $hasData_equipment = !empty($stats_equipment) && ($stats_equipment['total'] ?? 0) > 0;
                            ?>

                            <h3><i class="fas fa-clipboard-list"></i> Complaint Status (Equipment)</h3>

                            <?php if ($hasData_equipment): ?>
                                <div class="stat-chart">
                                    <canvas id="statusChart_equipment"></canvas>
                                </div>
                                <div class="stats-details">
                                    <div class="stat-item total">
                                        <span class="stat-label">Closed / Total</span>
                                        <span class="stat-value">
                                            <?= $stats_equipment['closed'] ?> / <?= $stats_equipment['total'] ?>
                                        </span>
                                    </div>
                                    <div class="stat-item pending">
                                        <span class="stat-label">Pending / In-Process</span>
                                        <span class="stat-value">
                                            <?= $stats_equipment['pending'] ?> / <?= $stats_equipment['inprocess'] ?>
                                        </span>
                                    </div>
                                </div>
                                <button class="download-btn" onclick="downloadChartAsImage('statusChart_equipment')">Download</button>
                            <?php else: ?>
                                <p style="text-align:center; padding:20px; color:#888;">No data to show</p>
                            <?php endif; ?>
                        </div>

                        <div class="stats-card">
                            <h3><i class="fas fa-flask"></i> Lab-wise Distribution</h3>
                            <div class="lab-stats">
                                <?php
                                // Get filtered and sorted lab complaints data
                                $labComplaints_equipments = getLabWiseComplaints($type_equipment);

                                if (!empty($labComplaints_equipments)) :
                                    // Display each lab's statistics
                                    foreach ($labComplaints_equipments as $lab): ?>
                                        <div class="lab-item">
                                            <h4><?= $lab['lab_location'] ?></h4>
                                            <div class="progress-bar">
                                                <div class="progress pending" style="width: <?= $lab['total'] > 0 ? round(($lab['pending'] / $lab['total']) * 100, 2) : 0 ?>%"></div>
                                                <div class="progress inprocess" style="width: <?= $lab['total'] > 0 ? round(($lab['inprocess'] / $lab['total']) * 100, 2) : 0 ?>%"></div>
                                                <div class="progress closed" style="width: <?= $lab['total'] > 0 ? round(($lab['closed'] / $lab['total']) * 100, 2) : 0 ?>%"></div>
                                                <div class="progress onhold" style="width: <?= $lab['total'] > 0 ? round(($lab['onhold'] / $lab['total']) * 100, 2) : 0 ?>%"></div>
                                            </div>
                                            <div class="lab-details">
                                                <span>Total: <?= $lab['total'] ?></span>
                                                <span class="pending">Pending: <?= $lab['pending'] ?></span>
                                                <span class="inprocess">In-Progress: <?= $lab['inprocess'] ?></span>
                                                <span class="closed">Closed: <?= $lab['closed'] ?></span>
                                                <span class="onhold">On-Hold: <?= $lab['onhold'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <p style="text-align:center; padding:20px; color:#888;">No data to show</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Side by side graph for Equipment tools avg day for solving complaints, Greater than equal to 100, and less than -->
                    <div class="stats-card">
                        <?php
                        // Get the data
                        $resolutionData_equipment = getToolResolutionData($type_equipment);
                        // echo "<script>console.log('resolutionData:', " . json_encode($resolutionData_equipment) . ")</script>";

                        $equipment_less_than_100 = $resolutionData_equipment['less_than_100'];
                        $equipment_more_than_100 = $resolutionData_equipment['more_than_100'];
                        ?>

                        <h3><i class="fas fa-clock"></i> Resolution Time(Equipment)</h3>
                        <div class="resolution-charts-container">

                            <?php if (!empty($equipment_more_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="equipment_resolutionChartMore100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('equipment_resolutionChartMore100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('equipment_resolutionChartMore100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for ≥ 100 Days</p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($equipment_less_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="equipment_resolutionChartLess100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('equipment_resolutionChartLess100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('equipment_resolutionChartLess100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for &lt; 100 Days</p>
                                </div>
                            <?php endif; ?>

                        </div>

                    </div>
                    <!-- Popup Modal for Equipment-resolution graph -->
                    <div id="equipment_resolutionChartMore100_modal" class="chart-modal">
                        <div class="chart-modal-content">
                            <span class="close-modal" onclick="closeChartModal('equipment_resolutionChartMore100')">&times;</span>
                            <canvas id="equipment_resolutionChartMore100_large"></canvas>
                        </div>
                    </div>
                    <div id="equipment_resolutionChartLess100_modal" class="chart-modal">
                        <div class="chart-modal-content">
                            <span class="close-modal" onclick="closeChartModal('equipment_resolutionChartLess100')">&times;</span>
                            <canvas id="equipment_resolutionChartLess100_large"></canvas>
                        </div>
                    </div>


                    <!-- Process :- higest complaint tool -->
                    <div class="stats-card highlight">
                        <?php $maxComplaints_process = getMaxComplaintsData($type_process);
                        // echo "<script>console.log('maxComplaints_process:', " . json_encode($maxComplaints_process) . ")</script>";
                        ?>
                        <h3><i class="fas fa-bolt"></i> Equipment with maximum (Process) complaints.</h3>
                        <div class="stats-list">
                            <?php foreach ($maxComplaints_process as $item): ?>
                                <div class="stat-item">
                                    <span class="stat-label"><?= $item['name'] ?></span>
                                    <span class="stat-value">
                                        <?= $item['count'] . ($item['count'] == 1 ? ' complaint' : ' complaints') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Side by side container for Process Status and Lab-wise Complaints for type Process Complaints -->
                    <div class="side-by-side-container">
                        <div class="stats-card">
                            <?php
                            // Get process complaint statistics
                            $stats_process = getComplaintStats($type_process);
                            ?>

                            <h3><i class="fas fa-clipboard-list"></i> Complaint Status(Process)</h3>
                            <div class="stat-chart">
                                <canvas id="statusChart_process"></canvas>
                            </div>
                            <div class="stats-details">
                                <div class="stat-item total">
                                    <span class="stat-label">Closed / Total</span>
                                    <span class="stat-value">
                                        <?= $stats_process['closed'] ?> / <?= $stats_process['total'] ?>
                                    </span>
                                </div>
                                <div class="stat-item pending">
                                    <span class="stat-label">Pending / In-Process</span>
                                    <span class="stat-value">
                                        <?= $stats_process['pending'] ?> / <?= $stats_process['inprocess'] ?>
                                    </span>
                                </div>
                            </div>
                            <button class="download-btn" onclick="downloadChartAsImage('statusChart_process')">Download</button>
                        </div>
                        <div class="stats-card">
                            <h3><i class="fas fa-flask"></i> Lab-wise Complaints</h3>
                            <div class="lab-stats">
                                <?php
                                // Get filtered and sorted lab complaints data
                                $labComplaints_process = getLabWiseComplaints($type_process);

                                // Display each lab's statistics
                                foreach ($labComplaints_process as $lab): ?>
                                    <div class="lab-item">
                                        <h4><?= $lab['lab_location'] ?></h4>
                                        <div class="progress-bar">
                                            <div class="progress pending" style="width: <?= round(($lab['pending'] / $lab['total']) * 100, 2) ?>%"></div>
                                            <div class="progress inprocess" style="width: <?= round(($lab['inprocess'] / $lab['total']) * 100, 2) ?>%"></div>
                                            <div class="progress closed" style="width: <?= round(($lab['closed'] / $lab['total']) * 100, 2) ?>%"></div>
                                            <div class="progress onhold" style="width: <?= round(($lab['onhold'] / $lab['total']) * 100, 2) ?>%"></div>
                                        </div>
                                        <div class="lab-details">
                                            <span>Total: <?= $lab['total'] ?></span>
                                            <span class="pending">Pending: <?= $lab['pending'] ?></span>
                                            <span class="inprocess">In-Progress: <?= $lab['inprocess'] ?></span>
                                            <span class="closed">Closed: <?= $lab['closed'] ?></span>
                                            <span class="onhold">On-Hold: <?= $lab['onhold'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Side by side graph for Process tools avg day for solving complaints, Greater than equal to 100, and less than -->
                    <div class="stats-card">
                        <?php

                        // Get the data
                        $resolutionData_process = getToolResolutionData($type_process);

                        // echo "<script>console.log('resolutionData:', " . json_encode($resolutionData_process) . ")</script>";

                        $process_less_than_100 = $resolutionData_process['less_than_100'];
                        $process_more_than_100 = $resolutionData_process['more_than_100'];
                        ?>

                        <h3><i class="fas fa-clock"></i> Resolution Time (Process)</h3>
                        <div class="resolution-charts-container">
                            <?php if (!empty($process_more_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="process_resolutionChartMore100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('process_resolutionChartMore100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('process_resolutionChartMore100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for ≥ 100 Days</p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($process_less_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="process_resolutionChartLess100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('process_resolutionChartLess100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('process_resolutionChartLess100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for &lt; 100 Days</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    <!-- Popup Modal for Process-resolution graph -->
                    <div id="process_resolutionChartMore100_modal" class="chart-modal">
                        <div class="chart-modal-content">
                            <span class="close-modal" onclick="closeChartModal('process_resolutionChartMore100')">&times;</span>
                            <canvas id="process_resolutionChartMore100_large"></canvas>
                        </div>
                    </div>
                    <div id="process_resolutionChartLess100_modal" class="chart-modal">
                        <div class="chart-modal-content">
                            <span class="close-modal" onclick="closeChartModal('process_resolutionChartLess100')">&times;</span>
                            <canvas id="process_resolutionChartLess100_large"></canvas>
                        </div>
                    </div>


                    <!-- Safety :- higest complaint tool -->
                    <div class="stats-card highlight">
                        <?php $maxComplaints_safety = getMaxComplaintsData($type_safety); ?>
                        <h3><i class="fas fa-bolt"></i> Equipment with maximum (Safety) complaints.</h3>
                        <div class="stats-list">
                            <?php if (!empty($maxComplaints_safety)): ?>
                                <?php foreach ($maxComplaints_safety as $item): ?>
                                    <div class="stat-item">
                                        <span class="stat-label"><?= $item['name'] ?></span>
                                        <span class="stat-value">
                                            <?= $item['count'] . ($item['count'] == 1 ? ' complaint' : ' complaints') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="stat-item">
                                    <span class="stat-label">No safety complaints found</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Side by side container for Safety Status and Lab-wise Complaints for type Safety Complaints -->
                    <div class="side-by-side-container">
                        <div class="stats-card">
                            <?php
                            // Get Safety complaint statistics
                            $stats_safety = getComplaintStats($type_safety);
                            $hasData = !empty($stats_safety) && isset($stats_safety['total']) && $stats_safety['total'] > 0;
                            ?>

                            <h3><i class="fas fa-clipboard-list"></i> Complaint Status (Safety)</h3>

                            <?php if ($hasData): ?>
                                <div class="stat-chart">
                                    <canvas id="statusChart_safety"></canvas>
                                </div>
                                <div class="stats-details">
                                    <div class="stat-item total">
                                        <span class="stat-label">Closed / Total</span>
                                        <span class="stat-value">
                                            <?= $stats_safety['closed'] ?? 0 ?> / <?= $stats_safety['total'] ?>
                                        </span>
                                    </div>
                                    <div class="stat-item pending">
                                        <span class="stat-label">Pending / In-Process</span>
                                        <span class="stat-value">
                                            <?= $stats_safety['pending'] ?? 0 ?> / <?= $stats_safety['inprocess'] ?? 0 ?>
                                        </span>
                                    </div>
                                </div>
                                <button class="download-btn" onclick="downloadChartAsImage('statusChart_safety')">Download</button>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <p>No safety complaint data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Safety equipments general names count -->
                        <div class="stats-card">
                            <?php
                            $safetyEquipments = getDeviceNames($type_safety);
                            // echo "<script>console.log('safetyEquipments', " . json_encode($safetyEquipments) . ");</script>";
                            ?>
                            <h3><i class="fas fa-chart-bar"></i>Safety Equipents</h3>
                            <div class="chart-container">
                                <canvas id="equipmentChart_safety"></canvas>
                            </div>
                            <button class="download-btn" onclick="downloadChartAsImage('equipmentChart_safety')">Download</button>
                        </div>
                    </div>

                    <!-- Side by side graph for Safety tools avg day for solving complaints, Greater than equal to 100, and less than -->
                    <div class="stats-card">
                        <?php
                        // Get the data
                        $resolutionData_safety = getToolResolutionData($type_safety);
                        // echo "<script>console.log('resolutionData_safety:', " . json_encode($resolutionData_safety) . ")</script>";

                        $safety_less_than_100 = $resolutionData_safety['less_than_100'];
                        $safety_more_than_100 = $resolutionData_safety['more_than_100'];
                        ?>

                        <h3><i class="fas fa-clock"></i> Resolution Time (Safety)</h3>

                        <div class="resolution-charts-container">
                            <?php if (!empty($safety_more_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="safety_resolutionChartMore100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('safety_resolutionChartMore100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('safety_resolutionChartMore100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for ≥ 100 Days</p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($safety_less_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="safety_resolutionChartLess100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('safety_resolutionChartLess100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('safety_resolutionChartLess100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for < 100 Days</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Popup Modal for Safety-resolution graph -->
                    <?php if (!empty($safety_more_than_100)) { ?>
                        <div id="safety_resolutionChartMore100_modal" class="chart-modal">
                            <div class="chart-modal-content">
                                <span class="close-modal" onclick="closeChartModal('safety_resolutionChartMore100')">&times;</span>
                                <canvas id="safety_resolutionChartMore100_large"></canvas>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if (!empty($safety_less_than_100)) { ?>
                        <div id="safety_resolutionChartLess100_modal" class="chart-modal">
                            <div class="chart-modal-content">
                                <span class="close-modal" onclick="closeChartModal('safety_resolutionChartLess100')">&times;</span>
                                <canvas id="safety_resolutionChartLess100_large"></canvas>
                            </div>
                        </div>
                    <?php } ?>


                    <!-- Facility :- higest complaint tool -->
                    <div class="stats-card highlight">
                        <?php $maxComplaints_facitily = getMaxComplaintsData($type_facility);
                        // echo "<script>console.log('maxComplaints_facitily:', " . json_encode($maxComplaints_facitily) . ")</script>";
                        ?>

                        <h3><i class="fas fa-bolt"></i> Equipment with maximum (Facility) complaints.</h3>
                        <div class="stats-list">
                            <?php if (!empty($maxComplaints_facitily)): ?>
                                <?php foreach ($maxComplaints_facitily as $item): ?>
                                    <div class="stat-item">
                                        <span class="stat-label"><?= $item['name'] ?></span>
                                        <span class="stat-value">
                                            <?= $item['count'] . ($item['count'] == 1 ? ' complaint' : ' complaints') ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="stat-item">
                                    <span class="stat-label">No safety complaints found</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Side by side container for Facilty Status and Lab-wise Complaints for type Facility Complaints -->
                    <div class="side-by-side-container">
                        <div class="stats-card">
                            <?php
                            // Get Facility complaint statistics
                            $stats_facility = getComplaintStats($type_facility);
                            $hasData = !empty($stats_facility) && isset($stats_facility['total']) && $stats_facility['total'] > 0;
                            // echo "<script>console.log('stats_facility:', " . json_encode($stats_facility) . ")</script>";
                            ?>

                            <h3><i class="fas fa-clipboard-list"></i> Complaint Status (Facility)</h3>

                            <?php if ($hasData): ?>
                                <div class="stat-chart">
                                    <canvas id="statusChart_facility"></canvas>
                                </div>
                                <div class="stats-details">
                                    <div class="stat-item total">
                                        <span class="stat-label">Closed / Total</span>
                                        <span class="stat-value">
                                            <?= $stats_facility['closed'] ?? 0 ?> / <?= $stats_facility['total'] ?>
                                        </span>
                                    </div>
                                    <div class="stat-item pending">
                                        <span class="stat-label">Pending / In-Process</span>
                                        <span class="stat-value">
                                            <?= $stats_facility['pending'] ?? 0 ?> / <?= $stats_facility['inprocess'] ?? 0 ?>
                                        </span>
                                    </div>
                                </div>
                                <button class="download-btn" onclick="downloadChartAsImage('statusChart_facility')">Download</button>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <p>No facility complaint data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="stats-card">
                            <?php
                            $facilityEquipments = getDeviceNames($type_facility);
                            // echo "<script>console.log('facilityEquipments', " . json_encode($facilityEquipments) . ");</script>";
                            ?>
                            <h3><i class="fas fa-chart-bar"></i>Facility Equipents</h3>
                            <div class="chart-container">
                                <canvas id="equipmentChart_facility"></canvas>
                            </div>
                            <button class="download-btn" onclick="downloadChartAsImage('equipmentChart_facility')">Download</button>
                        </div>
                    </div>

                    <!-- Side by side graph for Safety tools avg day for solving complaints, Greater than equal to 100, and less than -->
                    <div class="stats-card">
                        <?php
                        // Get the data
                        $resolutionData_facility = getToolResolutionData($type_facility);

                        // echo "<script>console.log('resolutionData_facility:', " . json_encode($resolutionData_facility) . ")</script>";

                        $facility_less_than_100 = $resolutionData_facility['less_than_100'];
                        $facility_more_than_100 = $resolutionData_facility['more_than_100'];
                        ?>

                        <h3><i class="fas fa-clock"></i> Resolution Time (Facility)</h3>

                        <div class="resolution-charts-container">
                            <?php if (!empty($facility_more_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="facility_resolutionChartMore100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('facility_resolutionChartMore100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('facility_resolutionChartMore100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for ≥ 100 Days</p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($facility_less_than_100)): ?>
                                <div>
                                    <div class="chart-container">
                                        <canvas id="facility_resolutionChartLess100"></canvas>
                                        <i class="fas fa-expand fullscreen-icon" onclick="openChartModal('facility_resolutionChartLess100')"></i>
                                    </div>
                                    <button class="download-btn" onclick="downloadChartAsImage('facility_resolutionChartLess100')">Download</button>
                                </div>
                            <?php else: ?>
                                <div class="no-resolution-data">
                                    <p>No data found for < 100 Days</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Popup Modal for Facility-resolution graph -->
                    <?php if (!empty($safety_more_than_100)) { ?>
                        <div id="facility_resolutionChartMore100_modal" class="chart-modal">
                            <div class="chart-modal-content">
                                <span class="close-modal" onclick="closeChartModal('facility_resolutionChartMore100')">&times;</span>
                                <canvas id="facility_resolutionChartMore100_large"></canvas>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($facility_less_than_100)) { ?>
                        <div id="facility_resolutionChartLess100_modal" class="chart-modal">
                            <div class="chart-modal-content">
                                <span class="close-modal" onclick="closeChartModal('facility_resolutionChartLess100')">&times;</span>
                                <canvas id="facility_resolutionChartLess100_large"></canvas>
                            </div>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</main>

<script src="../assets/js/chart.min.js"></script>
<script src="../assets/js/chartjs-plugin-datalabels.min.js"></script>

<script>
    // Status Chart - now fully responsive
    function createStatusChart(pending, inprocess, closed, onhold, chart_name) {
        const total = pending + inprocess + closed + onhold;
        if (total === 0) {

            return null;
        }

        const statusCtx = document.getElementById(chart_name).getContext('2d');
        return new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Process', 'Closed', 'On Hold'],
                datasets: [{
                    data: [
                        pending, inprocess, closed, onhold
                    ],
                    backgroundColor: [
                        '#ff6384',
                        '#36a2eb',
                        '#4bc0c0',
                        '#ffcd56'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    // downloadButton: {} // activate custom plugin
                }
            },
            // plugins: [downloadButtonPlugin] // register plugin   
        });
    }

    function downloadChartAsImage(chartId) {
        const canvas = document.getElementById(chartId);
        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = `${chartId}.png`;
        link.click();
    }


    // Resolution Time Chart - now fully responsive
    function createResolutionChart(data, chartId, title) {

        if (!data || data.length === 0) {
            // document.getElementById(chartId).closest('.chart-container').innerHTML =
            //     '<p class="no-data-message">No data available</p>';
            return null;
        }

        const resolutionCtx = document.getElementById(chartId).getContext('2d');

        // Define a nice color palette (you can customize these colors)
        const colorPalette = [
            'rgba(54, 162, 235, 0.7)', // Blue
            'rgba(255, 99, 132, 0.7)', // Red
            'rgba(75, 192, 192, 0.7)', // Teal
            'rgba(255, 159, 64, 0.7)', // Orange
            'rgba(153, 102, 255, 0.7)', // Purple
            'rgba(255, 205, 86, 0.7)', // Yellow
            'rgba(201, 203, 207, 0.7)' // Gray
        ];

        return new Chart(resolutionCtx, {
            type: 'bar',
            data: {
                labels: data.map(t => t.name),
                datasets: [{
                    label: 'Average Resolution Time (days)',
                    data: data.map(t => t.avg),
                    backgroundColor: data.map((_, index) =>
                        colorPalette[index % colorPalette.length]), // Cycle through palette
                    borderColor: data.map((_, index) =>
                        colorPalette[index % colorPalette.length].replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 14
                        }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const tool = data;
                                return `Total complaints: ${tool[index].total_complaints}\nTotal days: ${tool[index].sum_of_days}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Days'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tools'
                        },
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Create EquipmentPie Chart - now fully responsive
    function createEquipmentChart(equipmentData, chartId) {
        const labels = Object.keys(equipmentData);
        const data = Object.values(equipmentData);
        const total = data.reduce((sum, value) => sum + value, 0);

        if (total === 0) {
            document.getElementById(chartId).closest('.chart-container').innerHTML =
                '<p class="no-data-message">No equipment data available</p>';
            return null;
        }

        const ctx = document.getElementById(chartId).getContext('2d');
        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Equipment Count',
                    data: data,
                    backgroundColor: [
                        '#4bc0c0',
                        '#36a2eb',
                        '#ff6384',
                        '#ffcd56',
                        '#9966ff',
                        '#ff9f40',
                        '#8ac249'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function openChartModal(chartId) {
        const modalId = chartId + "_modal";
        const modalCanvasId = chartId + "_large";

        // Get original chart data (must be stored globally)
        const originalChart = Chart.getChart(chartId);
        if (!originalChart) return;

        // Show modal
        document.getElementById(modalId).style.display = "block";

        // If already drawn, destroy and recreate to avoid duplicates
        if (Chart.getChart(modalCanvasId)) {
            Chart.getChart(modalCanvasId).destroy();
        }

        // Create enlarged chart using the same data and options
        new Chart(document.getElementById(modalCanvasId).getContext('2d'), {
            type: originalChart.config.type,
            data: originalChart.config.data,
            options: {
                ...originalChart.config.options,
                maintainAspectRatio: false,
                responsive: true
            }
        });
    }

    function closeChartModal(chartId) {
        const modalId = chartId + "_modal";
        document.getElementById(modalId).style.display = "none";
    }




    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {

        // for piechart of Equipment Status
        const statsEquipment = <?php echo json_encode($stats_equipment); ?>;
        const statusChart_equipment = createStatusChart(
            statsEquipment.pending,
            statsEquipment.inprocess,
            statsEquipment.closed,
            statsEquipment.onhold,
            'statusChart_equipment'
        );

        // Create resolution charts for equipment
        const equipment_lessThan100 = <?php echo json_encode($equipment_less_than_100); ?>;
        const equipment_moreThan100 = <?php echo json_encode($equipment_more_than_100); ?>;
        const equipment_resolutionChartLess100 = createResolutionChart(
            equipment_lessThan100,
            'equipment_resolutionChartLess100',
            'Resolution Time < 100 Days'
        );
        const equipment_resolutionChartMore100 = createResolutionChart(
            equipment_moreThan100,
            'equipment_resolutionChartMore100',
            'Resolution Time ≥ 100 Days'
        );

        // for piechart of Process Status
        const statsProcess = <?php echo json_encode($stats_process); ?>;
        const statusChart_process = createStatusChart(
            statsProcess.pending,
            statsProcess.inprocess,
            statsProcess.closed,
            statsProcess.onhold,
            'statusChart_process'
        );
        // Create resolution charts for Process
        const process_lessThan100 = <?php echo json_encode($process_less_than_100); ?>;
        const process_moreThan100 = <?php echo json_encode($process_more_than_100); ?>;
        const process_resolutionChartLess100 = createResolutionChart(
            process_lessThan100,
            'process_resolutionChartLess100',
            'Resolution Time < 100 Days'
        );
        const process_resolutionChartMore100 = createResolutionChart(
            process_moreThan100,
            'process_resolutionChartMore100',
            'Resolution Time ≥ 100 Days'
        );

        // for piechart of Safety Status
        const statsSafety = <?php echo json_encode($stats_safety) ?>;
        const statusChart_safety = createStatusChart(
            statsSafety.pending,
            statsSafety.inprocess,
            statsSafety.closed,
            statsSafety.onhold,
            'statusChart_safety'
        );


        const safetyEquipmentData = <?php echo json_encode($safetyEquipments['deviceTypeCounts'] ?? []); ?>;
        const safetyEquipmentDataChart = createEquipmentChart(safetyEquipmentData, 'equipmentChart_safety');

        // Add this initialization for facility equipment chart:
        const facilityEquipmentData = <?php echo json_encode($facilityEquipments['deviceTypeCounts'] ?? []); ?>;
        const facilityEquipmentDataChart = createEquipmentChart(facilityEquipmentData, 'equipmentChart_facility');

        // Create resolution charts for Safety
        const safety_lessThan100 = <?php echo json_encode($safety_less_than_100); ?>;
        const safety_moreThan100 = <?php echo json_encode($safety_more_than_100); ?>;

        const safety_resolutionChartLess100 = createResolutionChart(
            safety_lessThan100,
            'safety_resolutionChartLess100',
            'Resolution Time < 100 Days'
        );

        const safety_resolutionChartMore100 = createResolutionChart(
            safety_moreThan100,
            'safety_resolutionChartMore100',
            'Resolution Time ≥ 100 Days'
        );

        // for piechart of Facility Status
        const statsFacility = <?php echo json_encode($stats_facility); ?>;
        const statusChart_facility = createStatusChart(
            statsFacility.pending,
            statsFacility.inprocess,
            statsFacility.closed,
            statsFacility.onhold,
            'statusChart_facility'
        );
        // Create resolution charts for Process
        const facility_lessThan100 = <?php echo json_encode($facility_less_than_100); ?>;
        const facility_moreThan100 = <?php echo json_encode($facility_more_than_100); ?>;

        const facility_resolutionChartLess100 = createResolutionChart(
            facility_lessThan100,
            'facility_resolutionChartLess100',
            'Resolution Time < 100 Days'
        );

        const facility_resolutionChartMore100 = createResolutionChart(
            facility_moreThan100,
            'facility_resolutionChartMore100',
            'Resolution Time ≥ 100 Days'
        );

        // Make charts responsive on window resize
        window.addEventListener('resize', function() {
            // for equipment complaints
            statusChart_equipment.resize();
            equipment_resolutionChartLess100.resize();
            equipment_resolutionChartMore100.resize();

            // for process complaints
            statusChart_process.resize();
            process_resolutionChartLess100.resize();
            process_resolutionChartMore100.resize();

            // for safety complaints
            if (statusChart_safety) statusChart_safety.resize();
            if (safety_resolutionChartLess100) safety_resolutionChartLess100.resize();
            if (safety_resolutionChartMore100) safety_resolutionChartMore100.resize();
            safetyEquipmentDataChart.resize();

            // for facility complaints
            if (statusChart_facility) statusChart_facility.resize();
            if (facility_resolutionChartLess100) facility_resolutionChartLess100.resize();
            if (facility_resolutionChartMore100) facility_resolutionChartMore100.resize();
            facilityEquipmentDataChart.resize();

        });
    });
</script>

<?php include("../includes/footer.php"); ?>
