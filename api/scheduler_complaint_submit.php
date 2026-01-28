<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * CORE FUNCTION
 * Used by Scheduler + HTTP API
 */
function scheduler_complaint_submit(array $input)
{
    $member_id  = $input['memberid'] ?? '';
    $machine_id = $input['tools_name'] ?? '';

    $process = !empty($input['process_develop'])
        ? base64_decode($input['process_develop'])
        : 'NULL';

    $anti = !empty($input['anti_contamination_develop'])
        ? base64_decode($input['anti_contamination_develop'])
        : 'NULL';

    $desc      = base64_decode($input['description'] ?? '') ?: '';
    $type      = $input['type'] ?? 0;
    $allocated = $input['allocated_to'] ?? NULL;

    if ($member_id === '' || $machine_id === '') {
        throw new Exception("memberid & tools_name are required");
    }

    /* =======================
       ORIGINAL INSERT LOGIC
       ======================= */
    insert_complaint(
        $member_id,
        $machine_id,
        $process,
        $anti,
        $desc,
        $type,
        $allocated
    );

    return [
        "status"  => "success",
        "message" => "Complaint submitted successfully"
    ];
}
