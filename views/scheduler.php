<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../config/connect.php';
require_once "common.php";
require_once "class.phpmailer.php"; // mail kept but commented

// Prevent API auto execution
define('SCHEDULER_INTERNAL_CALL', true);
require_once __DIR__ . "/../api/scheduler_complaint_submit.php";


/* =======================
   LOGGING
   ======================= */
$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

function logMsg($type, $msg)
{
    global $logDir;
    file_put_contents(
        "$logDir/scheduler_{$type}_" . date("Y_m_d") . ".log",
        "[" . date("Y-m-d H:i:s") . "] $msg\n",
        FILE_APPEND
    );
}

try {

    /* =======================
       FETCH DUE TASKS (DATE BASED)
       ======================= */
    $sql = "
        SELECT s.*,
               c.member_id,
               c.machine_id,
               c.process_develop,
               c.anti_contamination_develop,
               c.slotid,
               c.complaint_description,
               c.type,
               c.allocated_to
        FROM scheduler_daily_tasks s
        JOIN equipment_complaint c
          ON c.complaint_id = s.complaint_id
        WHERE s.status = 1
          AND DATE(FROM_UNIXTIME(s.trigger_date)) = CURDATE()
    ";

    $stmt = $db_equip->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            /* =======================
               BUILD PAYLOAD
               ======================= */
            $data = [
                "memberid"  => $row['member_id'],
                "tools_name"=> $row['machine_id'],
                "process_develop" => base64_encode($row['process_develop'] ?? ''),
                "anti_contamination_develop" => base64_encode($row['anti_contamination_develop'] ?? ''),
                "description" => base64_encode($row['complaint_description'] ?? ''),
                "slotid" => $row['slotid'],
                "type" => $row['type'],
                "allocated_to" => $row['allocated_to']
            ];

            /* =======================
               INSERT NEW COMPLAINT
               ======================= */
            try {
                scheduler_complaint_submit($data);
                logMsg("success", "Complaint inserted from scheduler");
            } catch (Exception $e) {
                logMsg("error", "Insert failed → " . $e->getMessage());
                continue;
            }

            /* =======================
               GET NEW COMPLAINT ID
               ======================= */
            $last = $db_equip->query("
                SELECT complaint_id
                FROM equipment_complaint
                ORDER BY complaint_id DESC
                LIMIT 1
            ")->fetch_assoc();

            $newComplaintId = $last['complaint_id'];

            /* =======================
               EMAIL NOTIFICATION (COMMENTED)
               ======================= */
            
            try {
               
                $to = get_email_user($row['allocated_to']);
                $from = get_email_user($row['created_by']);
                
                $team = match ($row['type']) {
                    1 => "Equipment",
                    2 => "Facility",
                    3 => "Safety",
                    4 => "Process",
                    default => "General"
                };

                $toolName = ($row['machine_id'] == 0)
                    ? "Miscellaneous"
                    : getToolName($row['machine_id']);

                
                $description = nl2br(htmlspecialchars($row['complaint_description']));
                $subject = "New Complaint -$team Submitted";
                $body = "<table border='0' width='100%'>\n".
                    "<tr><td colspan='2'><table><tr><td colspan='2'><b>A Complaint has been received for $team - (Complaint ID - $complaint_id)</b>,<br>\n".
                    "</td></tr><tr><td colspan='2' height='10'></td></tr>\n".
                
                    "<tr><td valign='top' align='right'><b>From: </b></td><td>".getName($row['member_id'])."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>For Tool: </b></td><td>".$toolName."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>Description: </b></td><td>".$description."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>Submitted at:</b></td><td>".date("F j, Y, g:i a", time())."</td></tr>\n".
                    "</table></td></tr></table>\n";
                    sendEmail($to,$from,$subject, $body);
                    logMsg("success", "Mail sent for complaint #$newComplaintId");

                } catch (Exception $e) {
                    logMsg("error", "Mail failed → " . $e->getMessage());
                }
            

            /* =======================
               CALCULATE NEXT TRIGGER
               ======================= */
            switch (strtolower(trim($row['timer']))) {
                case 'daily':    $next = strtotime('+1 day'); break;
                case 'weekly':   $next = strtotime('+1 week'); break;
                case 'biweekly': $next = strtotime('+3 days'); break;
                case 'monthly':  $next = strtotime('+1 month'); break;
                default:         $next = strtotime('+1 day');
            }

            $schedule_datetime = date("Y-m-d H:i:s", $next);

            /* =======================
               UPDATE SCHEDULER
               ======================= */
            $upd = $db_equip->prepare("
                UPDATE scheduler_daily_tasks
                SET trigger_date = ?,
                    schedule_datetime = ?
                WHERE complaint_id = ?
            ");
            $upd->bind_param("isi", $next, $schedule_datetime, $row['complaint_id']);
            $upd->execute();
            $upd->close();

            /* =======================
               TRACKING
               ======================= */
            $triggered_date = date("Y-m-d H:i:s");

            $stmt_track = $db_equip->prepare("
                INSERT INTO cron_scheduler_tracking
                (complaint_id, triggered_date)
                VALUES (?, ?)
            ");

            $stmt_track->bind_param("is", $newComplaintId, $triggered_date);
            $stmt_track->execute();
            $stmt_track->close();



            logMsg(
                "success",
                "Updated scheduler → timer={$row['timer']} | next={$schedule_datetime}"
            );
        }

    } else {
        logMsg("success", "No scheduler jobs found");
    }

    $stmt->close();

} catch (Exception $e) {
    logMsg("error", "Scheduler crashed → " . $e->getMessage());
}
