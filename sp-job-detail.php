<?php
session_start();
require_once 'db.php';

// Redirect if not logged in as SP
if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];
$spName = $_SESSION['sp_full_name'];

// 1. Get Job ID from URL and sanitize it
if(!isset($_GET['id']) || intval($_GET['id']) == 0) {
    header('Location: sp-my-jobs.php');
    exit;
}
$jobId = intval($_GET['id']);

// --- Handle STATUS UPDATE ---
if(isset($_POST['update_status'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['update_status']);
    
    // Validate the new status
    if($newStatus == 'assigned' || $newStatus == 'completed') {
        
        $updateQuery = "UPDATE proposals SET status = '$newStatus'";
        
        // If marking as completed, set the completion date
        if($newStatus == 'completed') {
            $updateQuery .= ", completed_at = NOW()";
        }
        
        $updateQuery .= " WHERE id = '$jobId' AND assigned_sp_id = '$spId'";
                        
        if(mysqli_query($conn, $updateQuery)) {
            $_SESSION['success_message'] = "Job status updated successfully! 👍";
            header("Location: sp-job-detail.php?id=$jobId");
            exit;
        } else {
            $error = "Failed to update status. Please try again.";
        }
    } else {
        $error = "Invalid status selected.";
    }
}


// 2. Fetch Job Details
$job = null;
$query = "SELECT p.*, u.full_name as student_name, u.profile_picture as student_pic, u.email as student_email
          FROM proposals p 
          JOIN users u ON u.id = p.user_id
          WHERE p.id = '$jobId' AND p.assigned_sp_id = '$spId'";
$res = mysqli_query($conn, $query);

if($res && mysqli_num_rows($res) > 0) {
    $job = mysqli_fetch_assoc($res);
} else {
    $_SESSION['error_message'] = "Job not found or you do not have permission to view it.";
    header('Location: sp-my-jobs.php');
    exit;
}

// Fetch SP profile picture (for top bar)
$spProfilePicture = '';
$spQuery = mysqli_query($conn, "SELECT profile_picture FROM service_providers WHERE id = '$spId'");
if($spQuery && mysqli_num_rows($spQuery) > 0) {
    $spData = mysqli_fetch_assoc($spQuery);
    $spProfilePicture = $spData['profile_picture'] ?? '';
}

// Check for messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $error ?? ($_SESSION['error_message'] ?? '');
unset($_SESSION['error_message']);


// --- Avatar Helper Functions (Copied from dashboard) ---
function getAvatarInitials($fullName) {
    $names = explode(' ', trim($fullName)); $initials = '';
    foreach ($names as $name) { if (!empty($name)) { $initials .= strtoupper(substr($name, 0, 1)); if (strlen($initials) >= 2) break; } }
    return $initials ?: 'U';
}
function getAvatarColor($fullName) {
    $colors = ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe','#00f2fe','#43e97b','#38f9d7','#ffecd2','#fcb69f','#a8edea','#fed6e3'];
    $index = ord(strtolower(substr($fullName, 0, 1))) % count($colors); return $colors[$index];
}
function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
    $r = max(0, min(255, $r + ($percent*255/100))); $g = max(0, min(255, $g + ($percent*255/100))); $b = max(0, min(255, $b + ($percent*255/100)));
    return sprintf("#%02x%02x%02x", $r,$g,$b);
}
function displayAvatar($profilePicture, $fullName, $size=40) {
    $initials = getAvatarInitials($fullName); $color = getAvatarColor($fullName);
    if(!empty($profilePicture) && file_exists('uploads/avatars/'.$profilePicture)) {
        return '<img src="uploads/avatars/'.htmlspecialchars($profilePicture).'" alt="Profile" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;border:2px solid #667eea;">';
    } else {
        return '<div style="width:'.$size.'px;height:'.$size.'px;background:linear-gradient(135deg,'.$color.','.adjustBrightness($color,-20).');border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:'.($size*0.4).'px;border:2px solid #667eea;">'.$initials.'</div>';
    }
}
function getDifficultyColor($difficulty) {
    $colors = ['Beginner' => '#22c55e', 'Intermediate' => '#f59e0b', 'Advanced' => '#ef4444'];
    return $colors[$difficulty] ?? '#6b7280';
}

// SIMPLIFIED Status Function
function getStatusInfo($status) {
    $info = [
        'assigned' => ['color' => '#f59e0b', 'bg' => '#fffbeb', 'icon' => 'fa-bolt', 'text' => 'On Process'],
        'completed' => ['color' => '#10b981', 'bg' => '#ecfdf5', 'icon' => 'fa-check-double', 'text' => 'Completed'],
        'open' => ['color' => '#6b7280', 'bg' => '#f3f4f6', 'icon' => 'fa-folder-open', 'text' => 'Open']
    ];
    // Default to 'assigned' if status is unknown but not completed
    if($status == 'completed') return $info['completed'];
    if($status == 'open') return $info['open'];
    
    return $info['assigned'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Details - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* --- ALL CSS FROM sp-dashboard.php (truncated for brevity) --- */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter','Segoe UI',sans-serif; }
body { background: linear-gradient(135deg, #f4f6fb 0%, #e8ecf7 100%); color:#333; min-height:100vh; }
.sidebar { width:280px; background:linear-gradient(135deg,#667eea,#764ba2); min-height:100vh; padding:2rem 1.5rem; color:white; position:fixed; z-index:100; box-shadow: 4px 0 20px rgba(0,0,0,0.1); }
.sidebar-header { text-align:center; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom: 2px solid rgba(255,255,255,0.2); }
.sidebar-header h2 { font-size:1.8rem; font-weight:800; }
.sidebar-header p { font-size:0.85rem; opacity:0.9; margin-top:0.3rem; }
.sidebar a { display:flex; align-items:center; gap:1rem; padding:1rem 1.2rem; text-decoration:none; color:white; border-radius:12px; margin-bottom:0.8rem; transition: all 0.3s ease; font-weight:500; position:relative; overflow:hidden; }
.sidebar a:before { content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:white; transform:scaleY(0); transition: transform 0.3s ease; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
.sidebar a.active:before { transform:scaleY(1); }
.sidebar a i { font-size:1.1rem; width:24px; text-align:center; }
.main { margin-left:280px; padding:2rem 2.5rem; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem; background:white; padding:1.5rem 2rem; border-radius:20px; box-shadow:0 10px 40px rgba(0,0,0,0.08); }
.topbar-left h1 { color:#1f2937; font-size:2rem; font-weight:800; }
.alert { padding:1.2rem 1.5rem; border-radius:15px; margin-bottom:2rem; display:flex; align-items:center; gap:1rem; font-weight:500; animation: slideDown 0.5s ease; }
@keyframes slideDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
.alert-error { background:#fef2f2; color:#dc2626; border-left:4px solid #dc2626; box-shadow: 0 4px 15px rgba(220,38,38,0.1); }
.alert-success { background:#ecfdf5; color:#059669; border-left:4px solid #059669; box-shadow: 0 4px 15px rgba(5,150,105,0.1); }
.alert i { font-size:1.3rem; }
.section { background:white; border-radius:20px; padding:2rem; box-shadow:0 10px 40px rgba(0,0,0,0.06); margin-bottom:2rem; }
.section-title { font-size:1.5rem; font-weight:800; color:#1f2937; display:flex; align-items:center; gap:0.8rem; margin-bottom:1.8rem; padding-bottom:1rem; border-bottom:2px solid #f3f4f6;}
.tag { background:#f3f4f6; color:#374151; padding:0.4rem 1rem; border-radius:20px; font-size:0.8rem; font-weight:600; border:1px solid #e5e7eb; }
.tag.difficulty { background: var(--diff-color); color:white; border-color: var(--diff-color); }

/* --- CSS FOR JOB DETAIL PAGE --- */
.job-detail-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 2.5rem;
    align-items: flex-start;
}
.job-main { display: flex; flex-direction: column; gap: 2rem; }

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f3f4f6;
    margin-bottom: 1.5rem;
}
.job-header h1 {
    font-size: 2.2rem;
    font-weight: 800;
    color: #1f2937;
    line-height: 1.3;
}
.job-header .budget {
    font-size: 2rem;
    font-weight: 800;
    color: #10b981;
    white-space: nowrap;
}
.job-description h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 1rem;
}
.job-description p {
    font-size: 1rem;
    line-height: 1.7;
    color: #4b5563;
    white-space: pre-wrap; /* Preserves formatting */
}

.job-sidebar { display: flex; flex-direction: column; gap: 2rem; position: sticky; top: 2rem; }

.meta-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.06);
}
.meta-card .section-title { margin-bottom: 1.5rem; padding-bottom: 0.8rem; font-size: 1.3rem; }
.student-info { display:flex; align-items:center; gap:1rem; }
.student-info .name { font-size: 1.1rem; font-weight: 700; color: #1f2937; }
.student-info .email { font-size: 0.9rem; color: #6b7280; }
.meta-card-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.2rem;
    font-size: 0.95rem;
}
.meta-card-item:last-child { margin-bottom: 0; }
.meta-card-item i {
    font-size: 1rem;
    color: #667eea;
    width: 20px;
    text-align: center;
}
.meta-card-item span { color: #6b7280; }
.meta-card-item strong { color: #374151; font-weight: 600; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 1.2rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    background: var(--status-bg);
    color: var(--status-color);
}
.status-badge i { font-size: 0.9rem; }

/* --- NEW Status Management CSS --- */
.status-management-box {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.current-status-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 15px;
    border: 2px solid #f3f4f6;
}
.current-status-display span {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
}
.status-actions {
    display: flex;
    gap: 1rem;
}
.status-actions form {
    flex: 1;
}
.btn-status {
    width: 100%;
    border: none;
    padding: 0.9rem 1.5rem;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}
.btn-status-process {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 15px rgba(245,158,11,0.3);
}
.btn-status-process:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245,158,11,0.4);
}
.btn-status-complete {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16,185,129,0.3);
}
.btn-status-complete:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16,185,129,0.4);
}
/* Style for disabled buttons */
.btn-status:disabled {
    background: #d1d5db;
    color: #6b7280;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}


@media(max-width:1024px) { 
    .sidebar { width:250px; } 
    .main { margin-left:250px; padding:1.5rem; }
    .job-detail-layout { grid-template-columns: 1fr; }
    .job-sidebar { position: static; }
}
@media(max-width:768px) { 
    .sidebar { width:100%; position:relative; min-height:auto; padding:1.5rem 1rem; } 
    .main { margin-left:0; padding:1rem; }
    .job-header { flex-direction: column; align-items: flex-start; }
    .status-actions { flex-direction: column; }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>🛠️ CollExpress</h2>
        <p>Service Provider Portal</p>
    </div>
    <a href="sp-dashboard.php">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>
    <a href="sp-tasks.php">
        <i class="fa-solid fa-briefcase"></i>
        <span>Available Tasks</span>
    </a>
    <a href="sp-my-jobs.php" class="active">
        <i class="fa-solid fa-list-check"></i>
        <span>My Jobs</span>
    </a>
    <a href="sp-earnings.php">
        <i class="fa-solid fa-wallet"></i>
        <span>Earnings</span>
    </a>
    <a href="sp-messages.php">
        <i class="fa-solid fa-envelope"></i>
        <span>Messages</span>
    </a>
    <a href="sp-profile.php">
        <i class="fa-solid fa-user-circle"></i>
        <span>Profile</span>
    </a>
    <a href="sp-logout.php" style="margin-top:2rem; opacity:0.8;">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
    </a>
</div>

<div class="main">
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <div class="job-detail-layout">
        
        <div class="job-main">
            <div class="section">
                <div class="job-header">
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="budget">₹<?php echo number_format($job['budget']); ?></div>
                </div>
                <div class="job-description">
                    <h3><i class="fa-solid fa-file-alt" style="color:#667eea;"></i> What the student wants:</h3>
                    <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                </div>
            </div>
            
            <?php $statusInfo = getStatusInfo($job['status']); ?>
            
            <div class="section status-management-box">
                <h2 class="section-title">
                    <i class="fa-solid fa-sliders" style="color:#667eea;"></i>
                    Manage Job Status
                </h2>
                
                <div class="current-status-display">
                    <span>Current Status:</span>
                    <strong class="status-badge" style="--status-color: <?php echo $statusInfo['color']; ?>; --status-bg: <?php echo $statusInfo['bg']; ?>;">
                        <i class="fa-solid <?php echo $statusInfo['icon']; ?>"></i>
                        <?php echo $statusInfo['text']; ?>
                    </strong>
                </div>

                <?php if($job['status'] != 'completed'): ?>
                    <div class="status-actions">
                        <form method="POST" class="status-form">
                            <button type="submit" name="update_status" value="assigned" class="btn-status btn-status-process" <?php echo ($job['status'] == 'assigned') ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-bolt"></i>
                                Set to "On Process"
                            </button>
                        </form>
                        <form method="POST" class="status-form">
                            <button type="submit" name="update_status" value="completed" class="btn-status btn-status-complete">
                                <i class="fa-solid fa-check-double"></i>
                                Mark as "Completed"
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; font-weight:600; color:#059669;">This job is completed. Well done! 🎉</p>
                <?php endif; ?>
            </div>
            
        </div>
        
        <div class="job-sidebar">
            <div class="meta-card">
                <h3 class="section-title"><i class="fa-solid fa-user-graduate"></i> Student Details</h3>
                <div class="student-info">
                    <?php echo displayAvatar($job['student_pic'], $job['student_name'], 50); ?>
                    <div>
                        <div class="name"><?php echo htmlspecialchars($job['student_name']); ?></div>
                        <div class="email"><?php echo htmlspecialchars($job['student_email']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="meta-card">
                 <h3 class="section-title"><i class="fa-solid fa-info-circle"></i> Job Info</h3>
                 
                 <div class="meta-card-item">
                    <i class="fa-solid fa-flag"></i>
                    <span>Status</span>
                    <strong class="status-badge" style="--status-color: <?php echo $statusInfo['color']; ?>; --status-bg: <?php echo $statusInfo['bg']; ?>;">
                        <i class="fa-solid <?php echo $statusInfo['icon']; ?>"></i>
                        <?php echo $statusInfo['text']; ?>
                    </strong>
                 </div>
                 
                 <div class="meta-card-item">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span>Deadline</span>
                    <strong><?php echo date('M d, Y', strtotime($job['deadline'])); ?></strong>
                 </div>
                 
                 <div class="meta-card-item">
                    <i class="fa-solid fa-layer-group"></i>
                    <span>Category</span>
                    <strong><?php echo htmlspecialchars($job['category']); ?></strong>
                 </div>
                 
                 <div class="meta-card-item">
                    <i class="fa-solid fa-clock"></i>
                    <span>Duration</span>
                    <strong><?php echo htmlspecialchars($job['duration']); ?></strong>
                 </div>

                 <div class="meta-card-item">
                    <i class="fa-solid fa-star"></i>
                    <span>Difficulty</span>
                    <strong class="tag difficulty" style="--diff-color: <?php echo getDifficultyColor($job['difficulty']); ?>">
                        <?php echo htmlspecialchars($job['difficulty']); ?>
                    </strong>
                 </div>
            </div>
        </div>
        
    </div>

</div>

<script>
// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Confirm before changing status
document.querySelectorAll('.status-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('button[name="update_status"]').value;
        let message = `Are you sure you want to update the status to "${action}"?`;
        if(action === 'completed') {
            message = 'Are you sure you want to mark this job as COMPLETED? This action is final.';
        }
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
});

</script>

</body>
</html>