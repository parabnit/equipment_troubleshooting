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
                "type" => $row['type'],
                "allocated_to" => $row['allocated_to']
            ];
            
           $tools_name = $row['machine_id']; // for email later
            /* =======================
               INSERT NEW COMPLAINT
               ======================= */
            try {
                $newInsertedId = scheduler_complaint_submit($data);
                logMsg("success", "Complaint inserted from scheduler");
            } catch (Exception $e) {
                logMsg("error", "Insert failed → " . $e->getMessage());
                exit(0);
            }

            /* =======================
               GET NEW COMPLAINT ID
               ======================= */
    
            $newComplaintId = $newInsertedId['id'];

            /* =======================
               EMAIL NOTIFICATION (COMMENTED)
               ======================= */
            
            try {
               
                $to = get_email_user($row['allocated_to']);
                $from = get_email_user($row['created_by']);
                $cc = "deepti.rukade@gmail.com,".$from;
		//$cc = "rohansghumare@gmail.com,".$from;
                $team = match ($row['type']) {
                    1 => "Equipment",
                    2 => "Facility",
                    3 => "Safety",
                    4 => "Process",
                    5 => "HR",
                    6 => "IT",
                    7 => "Purchase",
                    8 => "Training",
                    9 => "Inventory",
                    10 => "Admin",
                    default => "General"
                };

                $toolName = getComplaintToolName([
                    'type' => $row['type'],
                    'machine_id' => $tools_name
                ]);


                
                $description = nl2br(htmlspecialchars($row['complaint_description']));
                $subject = "New Task/Complaint -$team Submitted";
                $body = "<table border='0' width='100%'>\n".
                    "<tr><td colspan='2'><table><tr><td colspan='2'><b>A Task/Complaint has been received for $team - (Complaint ID - $newComplaintId)</b>,<br>\n".
                    "</td></tr><tr><td colspan='2' height='10'></td></tr>\n".
                
                    "<tr><td valign='top' align='right'><b>From: </b></td><td>".getName($row['member_id'])."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>For Tool: </b></td><td>".$toolName."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>Description: </b></td><td>".$description."</td></tr>\n".
                    "<tr><td valign='top' align='right'><b>Submitted at:</b></td><td>".date("F j, Y, g:i a", time())."</td></tr>\n".
                    "</table></td></tr></table>\n";
			sendEmailCC($to,$cc,$from,$subject, $body);
                    logMsg("success", "Mail sent for complaint #$newComplaintId");

                } catch (Exception $e) {
                    logMsg("error", "Mail failed → " . $e->getMessage());
                }
            

            /* =======================
            CALCULATE NEXT TRIGGER (INT TIMER)
            ======================= */
            $timer_days = (int)$row['timer'];  // timer is INT

            // NEXT trigger calculation (DATE-ONLY logic)
            if ($timer_days === 30) {
                // Monthly → first day of next month (00:00)
                $next = strtotime(date('Y-m-01', strtotime('+1 month')));
                $schedule_datetime = date("Y-m-d H:i:s", $next);

            } else {
                // Daily / Weekly / Bi-Weekly / Custom X days
                $next = strtotime(date('Y-m-d', strtotime("+$timer_days days")));
                $schedule_datetime = date("Y-m-d H:i:s", $next);
            }


            /* =======================
            UPDATE SCHEDULER
            ======================= */
            $upd = $db_equip->prepare("
                UPDATE scheduler_daily_tasks
                SET trigger_date = ?, trigger_datetime = ?
                WHERE complaint_id = ?
            ");

            // For one-time tasks, keep NULL if no next
            $upd->bind_param("isi", $next, $schedule_datetime, $row['complaint_id']);

            $upd->execute();
            $upd->close();


            /* =======================
               TRACKING
               ======================= */
          
            $oldcomplaint_id = $row['complaint_id'];

            $stmt_track = $db_equip->prepare("
                INSERT INTO cron_scheduler_tracking
                (old_complaint_id,new_complaint_id, triggered_date)
                VALUES (?, ?, now())
            ");

            $stmt_track->bind_param("ii",$oldcomplaint_id, $newComplaintId);
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
