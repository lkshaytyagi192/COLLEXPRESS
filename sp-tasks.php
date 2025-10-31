<?php
session_start();
require_once 'db.php';

// Auto-expire old proposals
if(file_exists('auto_expire_proposals.php')) {
    require_once 'auto_expire_proposals.php';
}

if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];
$error = '';

// Fetch SP data including phone
$spQuery = mysqli_query($conn, "SELECT full_name, profile_picture, phone_number FROM service_providers WHERE id='$spId'");
if($spQuery && mysqli_num_rows($spQuery) > 0) {
    $spData = mysqli_fetch_assoc($spQuery);
    $spName = $spData['full_name'];
    $spProfilePicture = $spData['profile_picture'] ?? '';
    $spPhone = $spData['phone_number'] ?? '';
} else {
    $spName = 'Service Provider';
    $spProfilePicture = '';
    $spPhone = '';
}

// CRITICAL: Check if SP has phone number
if(empty($spPhone)) {
    $_SESSION['error_message'] = "⚠️ You must add a phone number to your profile before viewing tasks. Clients need to contact you!";
    header('Location: sp-profile.php?phone_required=1');
    exit;
}

// Check for success/error messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Filter parameters
$categoryFilter = $_GET['category'] ?? '';
$difficultyFilter = $_GET['difficulty'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Build query - ONLY show non-expired proposals
$query = "SELECT p.*, u.full_name as student_name, u.profile_picture as student_pic 
          FROM proposals p 
          JOIN users u ON u.id = p.user_id 
          WHERE p.status='open' 
          AND (p.expired_at IS NULL OR p.expired_at > NOW())";

if(!empty($categoryFilter)) {
    $query .= " AND p.category = '" . mysqli_real_escape_string($conn, $categoryFilter) . "'";
}
if(!empty($difficultyFilter)) {
    $query .= " AND p.difficulty = '" . mysqli_real_escape_string($conn, $difficultyFilter) . "'";
}
if(!empty($searchQuery)) {
    $searchEscaped = mysqli_real_escape_string($conn, $searchQuery);
    $query .= " AND (p.title LIKE '%$searchEscaped%' OR p.description LIKE '%$searchEscaped%')";
}

$query .= " ORDER BY p.created_at DESC";

$proposals = [];
$res = mysqli_query($conn, $query);
if($res){
    while($row = mysqli_fetch_assoc($res)){
        $proposals[] = $row;
    }
}

// Helper Functions
function getAvatarInitials($fullName) {
    $names = explode(' ', trim($fullName));
    $initials = '';
    foreach ($names as $name) {
        if (!empty($name)) {
            $initials .= strtoupper(substr($name, 0, 1));
            if (strlen($initials) >= 2) break;
        }
    }
    return $initials ?: 'U';
}

function getAvatarColor($fullName) {
    $colors = ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe','#00f2fe'];
    $index = ord(strtolower(substr($fullName, 0, 1))) % count($colors);
    return $colors[$index];
}

function displayAvatar($profilePicture, $fullName, $size=40) {
    $initials = getAvatarInitials($fullName);
    $color = getAvatarColor($fullName);
    if(!empty($profilePicture) && file_exists('uploads/avatars/'.$profilePicture)) {
        return '<img src="uploads/avatars/'.$profilePicture.'" alt="'.$fullName.'" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;">';
    } else {
        return '<div style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;background:'.$color.';display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:'.($size/2).'px;">'.$initials.'</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tasks - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .task-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.3s; }
        .task-card:hover { transform: translateY(-5px); box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .task-header { display: flex; align-items: center; margin-bottom: 20px; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 10px; }
        .badge-category { background: #e3f2fd; color: #1976d2; }
        .badge-difficulty { background: #fff3e0; color: #f57c00; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 Available Tasks</h1>
            <p>Browse and accept tasks that match your skills</p>
        </div>

        <?php if($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if(empty($proposals)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                <h3>No Tasks Available</h3>
                <p>Check back later for new opportunities!</p>
            </div>
        <?php else: ?>
            <?php foreach($proposals as $p): ?>
                <div class="task-card">
                    <div class="task-header">
                        <?= displayAvatar($p['student_pic'], $p['student_name'], 50) ?>
                        <div style="margin-left: 15px; flex: 1;">
                            <h3 style="margin-bottom: 5px;"><?= htmlspecialchars($p['title']) ?></h3>
                            <p style="color: #666;">Posted by <?= htmlspecialchars($p['student_name']) ?></p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 24px; color: #667eea; font-weight: bold;">₹<?= number_format($p['budget'], 2) ?></div>
                        </div>
                    </div>

                    <div style="margin: 15px 0;">
                        <span class="badge badge-category"><?= ucfirst($p['category']) ?></span>
                        <span class="badge badge-difficulty"><?= ucfirst($p['difficulty']) ?></span>
                    </div>

                    <p style="color: #555; margin-bottom: 15px;"><?= nl2br(htmlspecialchars($p['description'])) ?></p>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee;">
                        <span style="color: #999; font-size: 14px;">
                            <i class="fa-solid fa-calendar"></i> Deadline: <?= date('M d, Y', strtotime($p['deadline'])) ?>
                        </span>
                        <a href="sp-job-detail.php?id=<?= $p['id'] ?>" class="btn btn-primary">
                            View Details & Apply
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
