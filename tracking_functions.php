<?php
// ============================================
// TRACKING HELPER FUNCTIONS
// ============================================

// Track when a user posts a new proposal
function logProposalPosted($conn, $userId, $proposalId, $amount) {
    mysqli_query($conn, "
        INSERT INTO user_activity_log 
        (user_id, activity_type, proposal_id, amount, details, created_at)
        VALUES 
        ('$userId', 'proposal_posted', '$proposalId', '$amount', 'New proposal posted', NOW())
    ");
    
    // Update user statistics
    mysqli_query($conn, "
        INSERT INTO user_statistics (user_id, total_proposals_posted)
        VALUES ('$userId', 1)
        ON DUPLICATE KEY UPDATE
        total_proposals_posted = total_proposals_posted + 1
    ");
}

// Track when a user accepts a service provider's response
function logProposalAccepted($conn, $userId, $proposalId, $spId, $amount) {
    mysqli_query($conn, "
        INSERT INTO user_activity_log 
        (user_id, activity_type, proposal_id, sp_id, amount, details, created_at)
        VALUES 
        ('$userId', 'proposal_accepted', '$proposalId', '$spId', '$amount', 'Proposal accepted', NOW())
    ");
    
    // Update user statistics
    mysqli_query($conn, "
        INSERT INTO user_statistics (user_id, total_services_ordered)
        VALUES ('$userId', 1)
        ON DUPLICATE KEY UPDATE
        total_services_ordered = total_services_ordered + 1
    ");
}

// Update SP statistics after any change
function updateSPStatistics($conn, $spId) {
    // Calculate average rating
    $ratingQuery = mysqli_query($conn, "
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
        FROM ratings 
        WHERE sp_id='$spId' AND rated_by='user'
    ");
    $ratingData = mysqli_fetch_assoc($ratingQuery);
    
    $avgRating = round($ratingData['avg_rating'] ?? 0, 2);
    $totalRatings = $ratingData['total_ratings'] ?? 0;
    
    // Calculate total earnings and jobs
    $earningsQuery = mysqli_query($conn, "
        SELECT SUM(budget) as total, COUNT(*) as count 
        FROM bookings 
        WHERE sp_id='$spId' AND status='completed'
    ");
    $earningsData = mysqli_fetch_assoc($earningsQuery);
    
    $totalEarnings = $earningsData['total'] ?? 0;
    $completedJobs = $earningsData['count'] ?? 0;
    
    // Count active jobs
    $activeJobsQuery = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE sp_id='$spId' AND status='active'
    ");
    $activeJobsData = mysqli_fetch_assoc($activeJobsQuery);
    $activeJobs = $activeJobsData['count'] ?? 0;
    
    // Update service_providers table
    mysqli_query($conn, "
        UPDATE service_providers 
        SET average_rating='$avgRating', 
            total_ratings='$totalRatings',
            total_jobs_completed='$completedJobs',
            total_earnings='$totalEarnings'
        WHERE id='$spId'
    ");
    
    // Update or insert sp_statistics
    mysqli_query($conn, "
        INSERT INTO sp_statistics 
        (sp_id, total_earnings, total_jobs_completed, total_jobs_active, average_rating, total_ratings)
        VALUES 
        ('$spId', '$totalEarnings', '$completedJobs', '$activeJobs', '$avgRating', '$totalRatings')
        ON DUPLICATE KEY UPDATE
        total_earnings='$totalEarnings',
        total_jobs_completed='$completedJobs',
        total_jobs_active='$activeJobs',
        average_rating='$avgRating',
        total_ratings='$totalRatings'
    ");
}
?>
