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
        : null;

    $anti = !empty($input['anti_contamination_develop'])
        ? base64_decode($input['anti_contamination_develop'])
        : null;

    $desc      = base64_decode($input['description'] ?? '') ?: '';
    $type      = $input['type'] ?? 0;
    $allocated = $input['allocated_to'] ?? null;

    // ✅ Daily task defaults
    $parent_id   = 0;
    $original_id = 0;
    $scheduler   = 1;   // or whatever flag you use for scheduler (0/1)

    if ($member_id === '' || $machine_id === '') {
        throw new Exception("memberid & tools_name are required");
    }

    /* =======================
       CORRECT INSERT CALL
       ======================= */
    $new_id = insert_complaint(
        $member_id,
        $machine_id,
        $process,
        $anti,
        $desc,
        $type,
        $allocated,     // team_head
        $parent_id,     // 0
        $original_id,   // 0
        $scheduler
    );

    return [
        "status"  => "success",
        "message" => "Complaint submitted successfully",
        "id"      => $new_id
    ];
}

