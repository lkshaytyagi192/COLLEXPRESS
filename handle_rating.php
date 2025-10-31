<?php
session_start();
require_once 'db.php';
require_once 'tracking_functions.php';

// USER RATES SERVICE PROVIDER
if(isset($_POST['rate_sp']) && isset($_SESSION['user_id'])){
    $bookingId = intval($_POST['booking_id']);
    $userId = intval($_SESSION['user_id']);
    $rating = floatval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, trim($_POST['review'] ?? ''));
    
    if($rating < 1 || $rating > 5){
        $_SESSION['error_message'] = "Invalid rating value";
        header("Location: my_orders.php");
        exit;
    }
    
    $bookingQuery = mysqli_query($conn, "
        SELECT b.*, p.category, p.title 
        FROM bookings b 
        JOIN proposals p ON p.id = b.proposal_id
        WHERE b.id='$bookingId' AND b.user_id='$userId' AND b.status='completed'
    ");
    
    if(mysqli_num_rows($bookingQuery) > 0){
        $booking = mysqli_fetch_assoc($bookingQuery);
        $spId = $booking['sp_id'];
        
        mysqli_begin_transaction($conn);
        
        try {
            mysqli_query($conn, "
                INSERT INTO ratings (booking_id, user_id, sp_id, rating, review, rated_by, created_at)
                VALUES ('$bookingId', '$userId', '$spId', '$rating', '$review', 'user', NOW())
                ON DUPLICATE KEY UPDATE rating='$rating', review='$review'
            ");
            
            mysqli_query($conn, "UPDATE bookings SET user_rated=1 WHERE id='$bookingId'");
            mysqli_query($conn, "UPDATE sp_portfolio SET rating_received='$rating' WHERE booking_id='$bookingId'");
            
            updateSPStatistics($conn, $spId);
            
            mysqli_query($conn, "
                INSERT INTO user_activity_log 
                (user_id, activity_type, booking_id, sp_id, details, created_at)
                VALUES 
                ('$userId', 'rating_given', '$bookingId', '$spId', 'Rated $rating stars', NOW())
            ");
            
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Thank you for your feedback! ⭐";
            header("Location: my_orders.php");
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to submit rating: " . $e->getMessage();
            header("Location: my_orders.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid booking or not yet completed";
        header("Location: my_orders.php");
        exit;
    }
}

// SERVICE PROVIDER RATES USER
if(isset($_POST['rate_user']) && isset($_SESSION['sp_id'])){
    $bookingId = intval($_POST['booking_id']);
    $spId = intval($_SESSION['sp_id']);
    $rating = floatval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, trim($_POST['review'] ?? ''));
    
    if($rating < 1 || $rating > 5){
        $_SESSION['error_message'] = "Invalid rating value";
        header("Location: sp-my-jobs.php");
        exit;
    }
    
    $bookingQuery = mysqli_query($conn, "
        SELECT * FROM bookings 
        WHERE id='$bookingId' AND sp_id='$spId' AND status='completed'
    ");
    
    if(mysqli_num_rows($bookingQuery) > 0){
        $booking = mysqli_fetch_assoc($bookingQuery);
        $userId = $booking['user_id'];
        
        mysqli_begin_transaction($conn);
        
        try {
            mysqli_query($conn, "
                INSERT INTO ratings (booking_id, user_id, sp_id, rating, review, rated_by, created_at)
                VALUES ('$bookingId', '$userId', '$spId', '$rating', '$review', 'sp', NOW())
                ON DUPLICATE KEY UPDATE rating='$rating', review='$review'
            ");
            
            mysqli_query($conn, "UPDATE bookings SET sp_rated=1 WHERE id='$bookingId'");
            
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Rating submitted successfully! ⭐";
            header("Location: sp-my-jobs.php");
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Failed to submit rating";
            header("Location: sp-my-jobs.php");
            exit;
        }
    }
}
?>
