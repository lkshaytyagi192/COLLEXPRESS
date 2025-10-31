<?php
session_start();
require_once 'db.php';

// Check if tracking_functions exists
if(file_exists('tracking_functions.php')) {
    require_once 'tracking_functions.php';
} else {
    function updateSPStatistics($conn, $spId) { /* dummy */ }
    function logProposalAccepted($conn, $userId, $proposalId, $spId, $amount) { /* dummy */ }
}

// ============================================
// USER ACCEPTS A SERVICE PROVIDER'S RESPONSE
// ============================================
if(isset($_POST['accept_response']) && isset($_SESSION['user_id'])){
    $responseId = intval($_POST['response_id']);
    $userId = intval($_SESSION['user_id']);
    
    $responseQuery = mysqli_query($conn, "
        SELECT pr.*, p.user_id, p.title, p.budget, p.id as proposal_id, p.category 
        FROM proposal_responses pr
        JOIN proposals p ON p.id = pr.proposal_id
        WHERE pr.id='$responseId' AND p.user_id='$userId' AND pr.status='pending'
    ");
    
    if(mysqli_num_rows($responseQuery) > 0){
        $response = mysqli_fetch_assoc($responseQuery);
        
        mysqli_begin_transaction($conn);
        
        try {
            mysqli_query($conn, "UPDATE proposal_responses SET status='accepted', accepted_at=NOW() WHERE id='$responseId'");
            mysqli_query($conn, "UPDATE proposals SET assigned_sp_id='{$response['sp_id']}', status='assigned', assigned_at=NOW() WHERE id='{$response['proposal_id']}'");
            mysqli_query($conn, "UPDATE proposal_responses SET status='rejected' WHERE proposal_id='{$response['proposal_id']}' AND id != '$responseId'");
            
            mysqli_query($conn, "
                INSERT INTO bookings 
                (user_id, sp_id, proposal_id, response_id, title, budget, category, status, created_at) 
                VALUES 
                ('{$response['user_id']}', '{$response['sp_id']}', '{$response['proposal_id']}', '$responseId', '".mysqli_real_escape_string($conn, $response['title'])."', '{$response['proposed_budget']}', '{$response['category']}', 'active', NOW())
            ");
            
            $userQuery = mysqli_query($conn, "SELECT full_name, phone_number FROM users WHERE id='$userId'");
            $userData = mysqli_fetch_assoc($userQuery);
            
            $message = "Congratulations! Your proposal has been accepted.\n\nClient Contact Information:\nName: {$userData['full_name']}\nPhone: {$userData['phone_number']}\n\nPlease contact them to coordinate the work.";
            
            mysqli_query($conn, "
                INSERT INTO messages 
                (sender_id, receiver_id, sender_type, receiver_type, message, created_at) 
                VALUES 
                ('$userId', '{$response['sp_id']}', 'user', 'sp', '".mysqli_real_escape_string($conn, $message)."', NOW())
            ");
            
            if(function_exists('logProposalAccepted')) {
                logProposalAccepted($conn, $userId, $response['proposal_id'], $response['sp_id'], $response['proposed_budget']);
            }
            
            if(function_exists('updateSPStatistics')) {
                updateSPStatistics($conn, $response['sp_id']);
            }
            
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Service provider accepted! Contact: {$response['sp_name']} - {$response['sp_phone']}";
            header("Location: my_proposals.php");
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to accept proposal: " . $e->getMessage();
            header("Location: my_proposals.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid response or already processed.";
        header("Location: my_proposals.php");
        exit;
    }
}

// ============================================
// USER REJECTS A SERVICE PROVIDER'S RESPONSE
// ============================================
if(isset($_POST['reject_response']) && isset($_SESSION['user_id'])){
    $responseId = intval($_POST['response_id']);
    $userId = intval($_SESSION['user_id']);
    
    $update = mysqli_query($conn, "
        UPDATE proposal_responses pr
        JOIN proposals p ON p.id = pr.proposal_id
        SET pr.status='rejected'
        WHERE pr.id='$responseId' AND p.user_id='$userId' AND pr.status='pending'
    ");
    
    if($update){
        $_SESSION['success_message'] = "Response rejected.";
    } else {
        $_SESSION['error_message'] = "Failed to reject response.";
    }
    header("Location: my_proposals.php");
    exit;
}

// ============================================
// SERVICE PROVIDER SUBMITS A PROPOSAL RESPONSE
// ============================================
if(isset($_POST['submit_response']) && isset($_SESSION['sp_id'])){
    $proposalId = intval($_POST['proposal_id']);
    $spId = intval($_SESSION['sp_id']);
    
    // CHECK IF SP HAS PHONE NUMBER
    $spDetailsQuery = mysqli_query($conn, "SELECT full_name, phone_number, email FROM service_providers WHERE id='$spId'");
    
    if(mysqli_num_rows($spDetailsQuery) > 0){
        $spDetails = mysqli_fetch_assoc($spDetailsQuery);
        
        // Validate phone number exists
        if(empty($spDetails['phone_number'])) {
            $_SESSION['error_message'] = "⚠️ You must add a phone number to your profile before submitting proposals. Clients need to contact you!";
            header("Location: sp-profile.php");
            exit;
        }
        
        $coverLetter = mysqli_real_escape_string($conn, trim($_POST['cover_letter']));
        $proposedBudget = floatval($_POST['proposed_budget']);
        
        // Check if proposal is still open and not expired
        $proposalCheck = mysqli_query($conn, "
            SELECT status FROM proposals 
            WHERE id='$proposalId' 
            AND status='open' 
            AND (expired_at IS NULL OR expired_at > NOW())
        ");
        
        if(mysqli_num_rows($proposalCheck) == 0) {
            $_SESSION['error_message'] = "This proposal is no longer available or has expired.";
            header("Location: sp-tasks.php");
            exit;
        }
        
        $insert = mysqli_query($conn, "
            INSERT INTO proposal_responses 
            (proposal_id, sp_id, sp_name, sp_phone, sp_email, cover_letter, proposed_budget, status, created_at) 
            VALUES 
            ('$proposalId', '$spId', '{$spDetails['full_name']}', '{$spDetails['phone_number']}', '{$spDetails['email']}', '$coverLetter', '$proposedBudget', 'pending', NOW())
        ");
        
        if($insert){
            $_SESSION['success_message'] = "Your proposal has been submitted! 🎉";
            header("Location: sp-tasks.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Failed to submit proposal: " . mysqli_error($conn);
            header("Location: sp-tasks.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Profile not found.";
        header("Location: sp-profile.php");
        exit;
    }
}

// ============================================
// MARK JOB AS COMPLETED (SP)
// ============================================
if(isset($_POST['mark_completed']) && isset($_SESSION['sp_id'])){
    $bookingId = intval($_POST['booking_id']);
    $spId = intval($_SESSION['sp_id']);
    
    $bookingQuery = mysqli_query($conn, "
        SELECT b.*, p.category, p.title, p.id as proposal_id 
        FROM bookings b
        LEFT JOIN proposals p ON p.id = b.proposal_id
        WHERE b.id='$bookingId' AND b.sp_id='$spId' AND b.status='active'
    ");
    
    if(mysqli_num_rows($bookingQuery) > 0){
        $booking = mysqli_fetch_assoc($bookingQuery);
        
        mysqli_begin_transaction($conn);
        
        try {
            mysqli_query($conn, "UPDATE bookings SET status='completed', completed_at=NOW() WHERE id='$bookingId'");
            mysqli_query($conn, "UPDATE proposals SET status='completed' WHERE id='{$booking['proposal_id']}'");
            
            mysqli_query($conn, "
                INSERT INTO sp_portfolio 
                (sp_id, booking_id, service_category, service_title, service_description, amount_earned, completion_date)
                VALUES 
                ('$spId', '$bookingId', '{$booking['category']}', '".mysqli_real_escape_string($conn, $booking['title'])."', '', '{$booking['budget']}', CURDATE())
            ");
            
            mysqli_query($conn, "
                INSERT INTO category_statistics (sp_id, category, total_jobs, total_earnings)
                VALUES ('$spId', '{$booking['category']}', 1, '{$booking['budget']}')
                ON DUPLICATE KEY UPDATE
                total_jobs = total_jobs + 1,
                total_earnings = total_earnings + {$booking['budget']}
            ");
            
            mysqli_query($conn, "
                INSERT INTO user_statistics 
                (user_id, total_spent, total_completed_services)
                VALUES 
                ('{$booking['user_id']}', '{$booking['budget']}', 1)
                ON DUPLICATE KEY UPDATE
                total_spent = total_spent + {$booking['budget']},
                total_completed_services = total_completed_services + 1
            ");
            
            mysqli_query($conn, "
                INSERT INTO user_activity_log 
                (user_id, activity_type, booking_id, sp_id, amount, details)
                VALUES 
                ('{$booking['user_id']}', 'service_completed', '$bookingId', '$spId', '{$booking['budget']}', 'Service completed: {$booking['title']}')
            ");
            
            if(function_exists('updateSPStatistics')) {
                updateSPStatistics($conn, $spId);
            }
            
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Job marked as completed! 🎉";
            header("Location: sp-my-jobs.php");
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to complete job: " . $e->getMessage();
            header("Location: sp-my-jobs.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid booking or already completed.";
        header("Location: sp-my-jobs.php");
        exit;
    }
}
?>
