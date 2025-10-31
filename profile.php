<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php'; 
// Avatar Helper Functions
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
    $colors = [
        '#667eea', '#764ba2', '#f093fb', '#f5576c',
        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
        '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3'
    ];
    
    $index = ord(strtolower(substr($fullName, 0, 1))) % count($colors);
    return $colors[$index];
}

function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($percent * 255 / 100)));
    $g = max(0, min(255, $g + ($percent * 255 / 100)));
    $b = max(0, min(255, $b + ($percent * 255 / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function displayAvatar($profilePicture, $fullName, $size = 40) {
    $initials = getAvatarInitials($fullName);
    $color = getAvatarColor($fullName);
    
    if (!empty($profilePicture) && file_exists('uploads/avatars/' . $profilePicture)) {
        return '<img src="uploads/avatars/' . htmlspecialchars($profilePicture) . '" 
                     alt="Profile" 
                     style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover;">';
    } else {
        return '<div style="width: ' . $size . 'px; 
                            height: ' . $size . 'px; 
                            background: linear-gradient(135deg, ' . $color . ', ' . adjustBrightness($color, -20) . '); 
                            border-radius: 50%; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            color: white; 
                            font-weight: 600;
                            font-size: ' . ($size * 0.4) . 'px;
                            border: 3px solid rgba(255,255,255,0.3);">
                    ' . $initials . '
                </div>';
    }
}

// Redirect if not logged in
if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'student';

// Handle profile picture upload
if(isset($_POST['upload_photo'])){
    $uploadDir = 'uploads/avatars/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if(in_array($_FILES['profile_photo']['type'], $allowedTypes) && $_FILES['profile_photo']['size'] <= $maxSize) {
            $extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $filename = $userId . '_' . $userType . '_' . time() . '.' . $extension;
            
            if(move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadDir . $filename)) {
                // Update database
                if($userType == 'student'){
                    $updateQuery = "UPDATE users SET profile_picture = '$filename' WHERE id = '$userId'";
                } else {
                    $updateQuery = "UPDATE service_providers SET profile_picture = '$filename' WHERE id = '$userId'";
                }
                
                if(mysqli_query($conn, $updateQuery)) {
                    $success = "Profile picture updated successfully!";
                } else {
                    $error = "Failed to save profile picture to database.";
                }
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type or file too large. Please upload JPG, PNG, or GIF under 2MB.";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle profile update
if(isset($_POST['update_profile'])){
    $fullName = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    
    if($userType == 'student'){
        $college = mysqli_real_escape_string($conn, $_POST['college']);
        $course = mysqli_real_escape_string($conn, $_POST['course']);
        $year = mysqli_real_escape_string($conn, $_POST['year']);
        
        $updateQuery = "UPDATE users SET 
                        full_name = '$fullName',
                        email = '$email',
                        phone = '$phone',
                        bio = '$bio',
                        location = '$location',
                        college = '$college',
                        course = '$course',
                        year = '$year'
                        WHERE id = '$userId'";
    } else {
        $skills = mysqli_real_escape_string($conn, $_POST['skills']);
        $experience = mysqli_real_escape_string($conn, $_POST['experience']);
        $hourly_rate = floatval($_POST['hourly_rate']);
        
        $updateQuery = "UPDATE service_providers SET 
                        full_name = '$fullName',
                        email = '$email',
                        phone = '$phone',
                        bio = '$bio',
                        location = '$location',
                        skills = '$skills',
                        experience = '$experience',
                        hourly_rate = '$hourly_rate'
                        WHERE id = '$userId'";
    }
    
    $updateResult = mysqli_query($conn, $updateQuery);
    if($updateResult){
        $success = "Profile updated successfully!";
        $_SESSION['full_name'] = $fullName;
    } else {
        $error = "Failed to update profile: " . mysqli_error($conn);
    }
}

// Fetch user profile data
$profileData = null;
if($userType == 'student'){
    $profileQuery = "SELECT * FROM users WHERE id = '$userId'";
} else {
    $profileQuery = "SELECT * FROM service_providers WHERE id = '$userId'";
}

$profileResult = mysqli_query($conn, $profileQuery);
if($profileResult && mysqli_num_rows($profileResult) > 0){
    $profileData = mysqli_fetch_assoc($profileResult);
}

// Get user statistics (previous stats code remains the same)
$stats = [];
if($userType == 'student'){
    $totalProposals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE user_id = '$userId'"))['count'];
    $activeProposals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE user_id = '$userId' AND status IN ('open', 'assigned', 'counter')"))['count'];
    $completedProposals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE user_id = '$userId' AND status = 'completed'"))['count'];
    $totalSpent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(budget), 0) as total FROM proposals WHERE user_id = '$userId' AND status = 'completed'"))['total'];
    
    $stats = [
        'total_proposals' => $totalProposals,
        'active_proposals' => $activeProposals,
        'completed_proposals' => $completedProposals,
        'total_spent' => $totalSpent
    ];
} else {
    $totalBids = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE assigned_sp_id = '$userId'"))['count'];
    $activeBids = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE assigned_sp_id = '$userId' AND status IN ('assigned', 'counter')"))['count'];
    $completedBids = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE assigned_sp_id = '$userId' AND status = 'completed'"))['count'];
    $totalEarned = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(budget), 0) as total FROM proposals WHERE assigned_sp_id = '$userId' AND status = 'completed'"))['total'];
    
    $stats = [
        'total_bids' => $totalBids,
        'active_bids' => $activeBids,
        'completed_bids' => $completedBids,
        'total_earned' => $totalEarned
    ];
}

// Get recent activity (previous code remains the same)
$recentActivity = [];
if($userType == 'student'){
    $activityQuery = "SELECT p.title, p.status, p.created_at, sp.full_name as sp_name 
                      FROM proposals p 
                      LEFT JOIN service_providers sp ON sp.id = p.assigned_sp_id
                      WHERE p.user_id = '$userId' 
                      ORDER BY p.created_at DESC LIMIT 5";
} else {
    $activityQuery = "SELECT p.title, p.status, p.created_at, u.full_name as student_name 
                      FROM proposals p 
                      INNER JOIN users u ON u.id = p.user_id
                      WHERE p.assigned_sp_id = '$userId' 
                      ORDER BY p.created_at DESC LIMIT 5";
}

$activityResult = mysqli_query($conn, $activityQuery);
if($activityResult){
    while($row = mysqli_fetch_assoc($activityResult)){
        $recentActivity[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: #f4f6fb;
    color: #333;
}

.sidebar {
    width: 250px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    padding: 2rem 1rem;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 1.5rem;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.8rem 1rem;
    text-decoration: none;
    color: white;
    border-radius: 10px;
    margin-bottom: 0.8rem;
    transition: all 0.3s ease;
}

.sidebar a:hover, 
.sidebar a.active {
    background: rgba(255, 255, 255, 0.2);
}

.main {
    margin-left: 250px;
    padding: 2rem;
    min-height: 100vh;
}

.profile-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 2rem;
    position: relative;
    z-index: 1;
}

.profile-avatar {
    position: relative;
}

.profile-details h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.profile-details p {
    opacity: 0.9;
    font-size: 1.1rem;
}

.user-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-top: 0.5rem;
    display: inline-block;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.profile-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.3rem;
    margin-bottom: 1.5rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.photo-upload-section {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 2px dashed #d1d5db;
    text-align: center;
}

.current-avatar {
    margin-bottom: 1rem;
}

.upload-form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.file-input-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-input-label {
    padding: 0.8rem 1.5rem;
    background: #667eea;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.file-input-label:hover {
    background: #5a67d8;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.btn {
    padding: 0.8rem 2rem;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    background: #5a67d8;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6b7280;
}

.btn-secondary:hover {
    background: #4b5563;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    transition: background 0.3s ease;
}

.activity-item:hover {
    background: #f8fafc;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-info h4 {
    color: #1f2937;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.activity-info p {
    color: #6b7280;
    font-size: 0.8rem;
}

.activity-status {
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-open { background: #dbeafe; color: #1e40af; }
.status-assigned { background: #dcfce7; color: #15803d; }
.status-counter { background: #fef9c3; color: #854d0e; }
.status-completed { background: #d1fae5; color: #059669; }

.error, .success {
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.success {
    background: #d1fae5;
    color: #059669;
    border: 1px solid #a7f3d0;
}

@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    
    .main {
        margin-left: 200px;
        padding: 1rem;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .profile-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>🎓 CollExpress</h2>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
    <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <?php if(isset($error)) echo '<div class="error">'.$error.'</div>'; ?>
    <?php if(isset($success)) echo '<div class="success">'.$success.'</div>'; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-info">
            <div class="profile-avatar">
                <?php echo displayAvatar($profileData['profile_picture'] ?? '', $profileData['full_name'] ?? 'User Name', 120); ?>
            </div>
            <div class="profile-details">
                <h1><?php echo htmlspecialchars($profileData['full_name'] ?? 'User Name'); ?></h1>
                <p><?php echo htmlspecialchars($profileData['email'] ?? 'No email provided'); ?></p>
                <span class="user-badge">
                    <?php echo ucfirst($userType); ?>
                    <?php if($userType == 'student' && isset($profileData['course'])): ?>
                        • <?php echo htmlspecialchars($profileData['course']); ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <?php if($userType == 'student'): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_proposals']; ?></div>
                <div class="stat-label">Total Proposals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_proposals']; ?></div>
                <div class="stat-label">Active Proposals</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_proposals']; ?></div>
                <div class="stat-label">Completed Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format($stats['total_spent']); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        <?php else: ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_bids']; ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_bids']; ?></div>
                <div class="stat-label">Active Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed_bids']; ?></div>
                <div class="stat-label">Completed Projects</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₹<?php echo number_format($stats['total_earned']); ?></div>
                <div class="stat-label">Total Earned</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="content-grid">
        <!-- Profile Information -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fa-solid fa-user-pen"></i>
                Profile Information
            </h2>

            <!-- Photo Upload Section -->
            <div class="photo-upload-section">
                <div class="current-avatar">
                    <?php echo displayAvatar($profileData['profile_picture'] ?? '', $profileData['full_name'] ?? 'User Name', 80); ?>
                </div>
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
                        <label for="profile_photo" class="file-input-label">
                            <i class="fa-solid fa-camera"></i>
                            Choose Photo
                        </label>
                    </div>
                    <button type="submit" name="upload_photo" class="btn btn-secondary">
                        <i class="fa-solid fa-upload"></i> Upload Photo
                    </button>
                    <small style="color: #6b7280;">Max 2MB. JPG, PNG, GIF allowed.</small>
                </form>
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($profileData['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profileData['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profileData['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($profileData['location'] ?? ''); ?>">
                    </div>
                </div>

                <?php if($userType == 'student'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>College</label>
                            <input type="text" name="college" value="<?php echo htmlspecialchars($profileData['college'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <input type="text" name="course" value="<?php echo htmlspecialchars($profileData['course'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select name="year">
                            <option value="">Select Year</option>
                            <option value="1st Year" <?php echo ($profileData['year'] ?? '') == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2nd Year" <?php echo ($profileData['year'] ?? '') == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3rd Year" <?php echo ($profileData['year'] ?? '') == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4th Year" <?php echo ($profileData['year'] ?? '') == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                            <option value="Graduate" <?php echo ($profileData['year'] ?? '') == 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Skills</label>
                        <input type="text" name="skills" value="<?php echo htmlspecialchars($profileData['skills'] ?? ''); ?>" placeholder="e.g. Web Development, Graphic Design, Data Analysis">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Experience</label>
                            <select name="experience">
                                <option value="">Select Experience</option>
                                <option value="Beginner (0-1 years)" <?php echo ($profileData['experience'] ?? '') == 'Beginner (0-1 years)' ? 'selected' : ''; ?>>Beginner (0-1 years)</option>
                                <option value="Intermediate (1-3 years)" <?php echo ($profileData['experience'] ?? '') == 'Intermediate (1-3 years)' ? 'selected' : ''; ?>>Intermediate (1-3 years)</option>
                                <option value="Experienced (3-5 years)" <?php echo ($profileData['experience'] ?? '') == 'Experienced (3-5 years)' ? 'selected' : ''; ?>>Experienced (3-5 years)</option>
                                <option value="Expert (5+ years)" <?php echo ($profileData['experience'] ?? '') == 'Expert (5+ years)' ? 'selected' : ''; ?>>Expert (5+ years)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hourly Rate (₹)</label>
                            <input type="number" name="hourly_rate" value="<?php echo $profileData['hourly_rate'] ?? ''; ?>" placeholder="500">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profileData['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn">
                    <i class="fa-solid fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- Recent Activity -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fa-solid fa-clock"></i>
                Recent Activity
            </h2>
            
            <?php if(!empty($recentActivity)): ?>
                <?php foreach($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <h4><?php echo htmlspecialchars($activity['title']); ?></h4>
                            <p>
                                <?php if($userType == 'student'): ?>
                                    <?php echo $activity['sp_name'] ? 'With: '.htmlspecialchars($activity['sp_name']) : 'No service provider assigned'; ?>
                                <?php else: ?>
                                    For: <?php echo htmlspecialchars($activity['student_name']); ?>
                                <?php endif; ?>
                                • <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                        <span class="activity-status status-<?php echo $activity['status']; ?>">
                            <?php echo ucfirst($activity['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #6b7280; padding: 2rem;">
                    <i class="fa-solid fa-history" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No recent activity found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Preview selected image
document.getElementById('profile_photo').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add image preview functionality here if needed
            console.log('File selected:', e.target.files[0].name);
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

</body>
</html>