<?php
// auto_expire_proposals.php - Run this via cron job or include at the top of sp-tasks.php
require_once 'db.php';

// Mark proposals as expired if no response after 5 minutes
mysqli_query($conn, "
    UPDATE proposals 
    SET status = 'expired', 
        expired_at = NOW()
    WHERE status = 'open' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    AND (SELECT COUNT(*) FROM proposal_responses WHERE proposal_id = proposals.id) = 0
");
?>
