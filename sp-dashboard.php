<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php'; 
// Redirect if not logged in as SP
if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];
$spName = $_SESSION['sp_full_name'];
$spEmail = $_SESSION['sp_email'] ?? '';

// Fetch SP profile picture
$spProfilePicture = '';
$spQuery = mysqli_query($conn, "SELECT profile_picture FROM service_providers WHERE id = '$spId'");
if($spQuery && mysqli_num_rows($spQuery) > 0) {
    $spData = mysqli_fetch_assoc($spQuery);
    $spProfilePicture = $spData['profile_picture'] ?? '';
}

// Accept a proposal
if(isset($_POST['accept_proposal'])){
    $proposalId = intval($_POST['proposal_id']);
    
    $update = mysqli_query($conn, "UPDATE proposals 
                                   SET assigned_sp_id='$spId', status='assigned' 
                                   WHERE id='$proposalId' AND status='open'");
    if($update){
        $_SESSION['success_message'] = "Task accepted successfully! 🎉";
        header("Location: sp-dashboard.php");
        exit;
    } else {
        $error = "Failed to accept task. Please try again.";
    }
}

// Check for success message
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Fetch available proposals (status=open)
$proposals = [];
$res = mysqli_query($conn, "SELECT p.*, u.full_name as student_name, u.profile_picture as student_pic
                            FROM proposals p 
                            JOIN users u ON u.id = p.user_id
                            WHERE p.status='open'
                            ORDER BY p.created_at DESC");
if($res){
    while($row = mysqli_fetch_assoc($res)){
        $proposals[] = $row;
    }
}

// Fetch SP's active tasks
$activeTasks = [];
$resActive = mysqli_query($conn, "SELECT p.*, u.full_name as student_name, u.profile_picture as student_pic
                                  FROM proposals p 
                                  JOIN users u ON u.id = p.user_id
                                  WHERE p.assigned_sp_id='$spId' AND p.status='assigned'
                                  ORDER BY p.deadline ASC");
if($resActive){
    while($row = mysqli_fetch_assoc($resActive)){
        $activeTasks[] = $row;
    }
}

// SP stats
$totalEarnings = 0;
$activeJobsCount = 0;
$completedJobs = 0;
$rating = 4.8; // Default rating

// Total earnings
$resEarnings = mysqli_query($conn, "SELECT SUM(budget) as total FROM proposals WHERE assigned_sp_id='$spId' AND status='completed'");
if($resEarnings && $row = mysqli_fetch_assoc($resEarnings)){
    $totalEarnings = $row['total'] ?? 0;
}

// Active jobs count
$activeJobsCount = count($activeTasks);

// Completed jobs
$resCompleted = mysqli_query($conn, "SELECT COUNT(*) as count FROM proposals WHERE assigned_sp_id='$spId' AND status='completed'");
if($resCompleted && $row = mysqli_fetch_assoc($resCompleted)){
    $completedJobs = $row['count'];
}

// Fetch rating if exists
$resRating = mysqli_query($conn, "SELECT rating FROM service_providers WHERE id='$spId'");
if($resRating && $row = mysqli_fetch_assoc($resRating)){
    $rating = $row['rating'] ?? 4.8;
}

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
    $colors = ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe','#00f2fe','#43e97b','#38f9d7','#ffecd2','#fcb69f','#a8edea','#fed6e3'];
    $index = ord(strtolower(substr($fullName, 0, 1))) % count($colors);
    return $colors[$index];
}

function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex,0,2));
    $g = hexdec(substr($hex,2,2));
    $b = hexdec(substr($hex,4,2));
    $r = max(0, min(255, $r + ($percent*255/100)));
    $g = max(0, min(255, $g + ($percent*255/100)));
    $b = max(0, min(255, $b + ($percent*255/100)));
    return sprintf("#%02x%02x%02x", $r,$g,$b);
}

function displayAvatar($profilePicture, $fullName, $size=40) {
    $initials = getAvatarInitials($fullName);
    $color = getAvatarColor($fullName);
    if(!empty($profilePicture) && file_exists('uploads/avatars/'.$profilePicture)) {
        return '<img src="uploads/avatars/'.htmlspecialchars($profilePicture).'" alt="Profile" style="width:'.$size.'px;height:'.$size.'px;border-radius:50%;object-fit:cover;border:2px solid #667eea;">';
    } else {
        return '<div style="width:'.$size.'px;height:'.$size.'px;background:linear-gradient(135deg,'.$color.','.adjustBrightness($color,-20).');border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:'.($size*0.4).'px;border:2px solid #667eea;">'.$initials.'</div>';
    }
}

function getDifficultyColor($difficulty) {
    $colors = [
        'Beginner' => '#22c55e',
        'Intermediate' => '#f59e0b',
        'Advanced' => '#ef4444'
    ];
    return $colors[$difficulty] ?? '#6b7280';
}

function getCategoryIcon($category) {
    $icons = [
        'Web Development' => 'fa-code',
        'Mobile App' => 'fa-mobile-alt',
        'Design & Graphics' => 'fa-paint-brush',
        'Data Science' => 'fa-chart-bar',
        'Writing & Content' => 'fa-pen',
        'Research' => 'fa-search',
        'Video Editing' => 'fa-video',
        'Other' => 'fa-tools'
    ];
    return $icons[$category] ?? 'fa-briefcase';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Provider Dashboard - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter','Segoe UI',sans-serif; }
body { background: linear-gradient(135deg, #f4f6fb 0%, #e8ecf7 100%); color:#333; min-height:100vh; }

/* Sidebar */
.sidebar { 
    width:280px; 
    background:linear-gradient(135deg,#667eea,#764ba2); 
    min-height:100vh; 
    padding:2rem 1.5rem; 
    color:white; 
    position:fixed; 
    z-index:100;
    box-shadow: 4px 0 20px rgba(0,0,0,0.1);
}
.sidebar-header { 
    text-align:center; 
    margin-bottom:2.5rem; 
    padding-bottom:1.5rem;
    border-bottom: 2px solid rgba(255,255,255,0.2);
}
.sidebar-header h2 { 
    font-size:1.8rem; 
    font-weight:800; 
    letter-spacing:1px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}
.sidebar-header p { 
    font-size:0.85rem; 
    opacity:0.9; 
    margin-top:0.3rem;
}

.sidebar a { 
    display:flex; 
    align-items:center; 
    gap:1rem; 
    padding:1rem 1.2rem; 
    text-decoration:none; 
    color:white; 
    border-radius:12px; 
    margin-bottom:0.8rem; 
    transition: all 0.3s ease;
    font-weight:500;
    position:relative;
    overflow:hidden;
}
.sidebar a:before {
    content:'';
    position:absolute;
    left:0; top:0; bottom:0;
    width:4px;
    background:white;
    transform:scaleY(0);
    transition: transform 0.3s ease;
}
.sidebar a:hover, .sidebar a.active { 
    background: rgba(255,255,255,0.2); 
    transform: translateX(5px);
}
.sidebar a.active:before {
    transform:scaleY(1);
}
.sidebar a i { 
    font-size:1.1rem; 
    width:24px;
    text-align:center;
}

/* Main Content */
.main { margin-left:280px; padding:2rem 2.5rem; }

/* Top Bar */
.topbar { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:2.5rem; 
    background:white; 
    padding:1.5rem 2rem; 
    border-radius:20px; 
    box-shadow:0 10px 40px rgba(0,0,0,0.08);
}
.topbar-left h1 { 
    color:#1f2937; 
    font-size:2rem; 
    font-weight:800; 
    margin-bottom:0.3rem;
}
.topbar-left p { 
    color:#6b7280; 
    font-size:0.95rem;
}
.profile-section { 
    display:flex; 
    align-items:center; 
    gap:1rem; 
    cursor:pointer; 
    padding:0.6rem 1.2rem; 
    border-radius:15px; 
    transition: all 0.3s ease;
    background:#f9fafb;
}
.profile-section:hover { 
    background:#f3f4f6; 
    transform:translateY(-2px);
}
.profile-info { text-align:right; }
.profile-info .name { 
    font-weight:700; 
    color:#1f2937; 
    font-size:0.95rem;
}
.profile-info .role { 
    font-size:0.8rem; 
    color:#6b7280;
}

/* Stats Grid */
.stats-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); 
    gap:1.5rem; 
    margin-bottom:2.5rem;
}
.stat-card { 
    background:white; 
    padding:1.8rem; 
    border-radius:20px; 
    box-shadow:0 10px 40px rgba(0,0,0,0.06); 
    position:relative;
    overflow:hidden;
    transition: all 0.3s ease;
}
.stat-card:hover { 
    transform:translateY(-5px); 
    box-shadow:0 15px 50px rgba(0,0,0,0.12);
}
.stat-card:before {
    content:'';
    position:absolute;
    top:0; right:0;
    width:100px; height:100px;
    background: var(--stat-color);
    border-radius:50%;
    transform:translate(40%, -40%);
    opacity:0.1;
}
.stat-icon { 
    width:56px; 
    height:56px; 
    background: var(--stat-color); 
    border-radius:15px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    margin-bottom:1rem;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.stat-icon i { 
    font-size:1.5rem; 
    color:white;
}
.stat-value { 
    font-size:2rem; 
    font-weight:800; 
    color:#1f2937; 
    margin-bottom:0.3rem;
}
.stat-label { 
    color:#6b7280; 
    font-size:0.9rem; 
    font-weight:500;
}

/* Alert Messages */
.alert { 
    padding:1.2rem 1.5rem; 
    border-radius:15px; 
    margin-bottom:2rem; 
    display:flex; 
    align-items:center; 
    gap:1rem; 
    font-weight:500;
    animation: slideDown 0.5s ease;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-20px); }
    to { opacity:1; transform:translateY(0); }
}
.alert-error { 
    background:#fef2f2; 
    color:#dc2626; 
    border-left:4px solid #dc2626;
    box-shadow: 0 4px 15px rgba(220,38,38,0.1);
}
.alert-success { 
    background:#ecfdf5; 
    color:#059669; 
    border-left:4px solid #059669;
    box-shadow: 0 4px 15px rgba(5,150,105,0.1);
}
.alert i { font-size:1.3rem; }

/* Section Container */
.section { 
    background:white; 
    border-radius:20px; 
    padding:2rem; 
    box-shadow:0 10px 40px rgba(0,0,0,0.06); 
    margin-bottom:2rem;
}
.section-header { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin-bottom:1.8rem;
    padding-bottom:1rem;
    border-bottom:2px solid #f3f4f6;
}
.section-title { 
    font-size:1.5rem; 
    font-weight:800; 
    color:#1f2937;
    display:flex;
    align-items:center;
    gap:0.8rem;
}
.section-title i { 
    color:#667eea;
    font-size:1.3rem;
}
.view-all { 
    color:#667eea; 
    text-decoration:none; 
    font-weight:600; 
    padding:0.5rem 1rem;
    border-radius:8px;
    transition: all 0.3s ease;
}
.view-all:hover { 
    background:#f3f4f6;
}

/* Task Cards */
.task-grid { 
    display:grid; 
    gap:1.5rem;
}
.task-card { 
    border:2px solid #e5e7eb; 
    border-radius:15px; 
    padding:1.8rem; 
    transition: all 0.3s ease;
    background:#fafbfc;
    position:relative;
    overflow:hidden;
}
.task-card:hover { 
    border-color:#667eea; 
    transform:translateY(-3px); 
    box-shadow:0 10px 30px rgba(102,126,234,0.15);
    background:white;
}
.task-card:before {
    content:'';
    position:absolute;
    top:0; left:0;
    width:4px; height:100%;
    background:linear-gradient(to bottom, #667eea, #764ba2);
    transform:scaleY(0);
    transition: transform 0.3s ease;
}
.task-card:hover:before {
    transform:scaleY(1);
}

.task-header { 
    display:flex; 
    justify-content:space-between; 
    align-items:flex-start; 
    margin-bottom:1rem;
}
.task-title { 
    font-size:1.2rem; 
    font-weight:700; 
    color:#1f2937; 
    margin-bottom:0.5rem;
    line-height:1.4;
}
.task-budget { 
    font-size:1.5rem; 
    font-weight:800; 
    color:#10b981;
    white-space:nowrap;
}

.task-meta { 
    display:flex; 
    gap:1.5rem; 
    margin-bottom:1rem; 
    flex-wrap:wrap;
}
.task-meta-item { 
    display:flex; 
    align-items:center; 
    gap:0.5rem; 
    color:#6b7280; 
    font-size:0.85rem;
}
.task-meta-item i { 
    color:#667eea;
    font-size:0.9rem;
}

.task-description { 
    color:#4b5563; 
    line-height:1.6; 
    margin-bottom:1.2rem;
    font-size:0.95rem;
}

.task-tags { 
    display:flex; 
    gap:0.6rem; 
    flex-wrap:wrap; 
    margin-bottom:1.2rem;
}
.tag { 
    background:#f3f4f6; 
    color:#374151; 
    padding:0.4rem 1rem; 
    border-radius:20px; 
    font-size:0.8rem; 
    font-weight:600;
    border:1px solid #e5e7eb;
    transition: all 0.3s ease;
}
.tag:hover {
    background:#667eea;
    color:white;
    border-color:#667eea;
}
.tag.difficulty { 
    background: var(--diff-color); 
    color:white;
    border-color: var(--diff-color);
}

.task-footer { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding-top:1rem; 
    border-top:1px solid #e5e7eb;
}
.student-info { 
    display:flex; 
    align-items:center; 
    gap:0.8rem;
}
.student-name { 
    font-weight:600; 
    color:#374151;
    font-size:0.9rem;
}

.btn-accept { 
    background:linear-gradient(135deg,#10b981,#059669); 
    color:white; 
    border:none; 
    padding:0.8rem 2rem; 
    border-radius:10px; 
    font-weight:700; 
    cursor:pointer; 
    transition: all 0.3s ease;
    display:flex;
    align-items:center;
    gap:0.5rem;
    box-shadow: 0 4px 15px rgba(16,185,129,0.3);
}
.btn-accept:hover { 
    transform:translateY(-2px); 
    box-shadow: 0 6px 20px rgba(16,185,129,0.4);
}
.btn-accept:active {
    transform:translateY(0);
}

/* Empty State */
.empty-state { 
    text-align:center; 
    padding:4rem 2rem;
    color:#6b7280;
}
.empty-state i { 
    font-size:4rem; 
    color:#d1d5db; 
    margin-bottom:1rem;
    opacity:0.5;
}
.empty-state h3 { 
    font-size:1.3rem; 
    margin-bottom:0.5rem;
    color:#374151;
}
.empty-state p { 
    font-size:0.95rem;
}

/* Tabs */
.tabs { 
    display:flex; 
    gap:0.5rem; 
    margin-bottom:2rem;
    background:#f3f4f6;
    padding:0.5rem;
    border-radius:12px;
}
.tab { 
    flex:1; 
    padding:0.8rem 1.5rem; 
    background:transparent; 
    border:none; 
    border-radius:8px; 
    font-weight:600; 
    cursor:pointer; 
    transition: all 0.3s ease;
    color:#6b7280;
}
.tab.active { 
    background:white; 
    color:#667eea;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.tab:hover:not(.active) {
    color:#374151;
}

/* Responsive */
@media(max-width:1024px) { 
    .sidebar { width:250px; } 
    .main { margin-left:250px; padding:1.5rem; }
}
@media(max-width:768px) { 
    .sidebar { 
        width:100%; 
        position:relative; 
        min-height:auto;
        padding:1.5rem 1rem;
    } 
    .main { margin-left:0; padding:1rem; }
    .topbar { 
        flex-direction:column; 
        gap:1rem; 
        text-align:center; 
    }
    .stats-grid { 
        grid-template-columns:1fr; 
    }
    .task-header {
        flex-direction:column;
        gap:1rem;
    }
    .task-footer {
        flex-direction:column;
        gap:1rem;
        align-items:flex-start;
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>🛠️ CollExpress</h2>
        <p>Service Provider Portal</p>
    </div>
    <a href="sp-dashboard.php" class="active">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Dashboard</span>
    </a>
    <a href="sp-tasks.php">
        <i class="fa-solid fa-briefcase"></i>
        <span>Available Tasks</span>
    </a>
    <a href="sp-my-jobs.php">
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
    <?php if(isset($error)): ?>
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

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $spName)[0]); ?>! 👋</h1>
            <p>Here's what's happening with your tasks today</p>
        </div>
        <div class="profile-section" onclick="window.location.href='sp-profile.php'">
            <?php echo displayAvatar($spProfilePicture, $spName, 45); ?>
            <div class="profile-info">
                <div class="name"><?php echo htmlspecialchars($spName); ?></div>
                <div class="role">Service Provider</div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card" style="--stat-color: #10b981;">
            <div class="stat-icon">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div class="stat-value">₹<?php echo number_format($totalEarnings); ?></div>
            <div class="stat-label">Total Earnings</div>
        </div>
        
        <div class="stat-card" style="--stat-color: #f59e0b;">
            <div class="stat-icon">
                <i class="fa-solid fa-briefcase"></i>
            </div>
            <div class="stat-value"><?php echo $activeJobsCount; ?></div>
            <div class="stat-label">Active Jobs</div>
        </div>
        
        <div class="stat-card" style="--stat-color: #667eea;">
            <div class="stat-icon">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div class="stat-value"><?php echo $completedJobs; ?></div>
            <div class="stat-label">Completed Jobs</div>
        </div>
        
        <div class="stat-card" style="--stat-color: #f59e0b;">
            <div class="stat-icon">
                <i class="fa-solid fa-star"></i>
            </div>
            <div class="stat-value"><?php echo number_format($rating, 1); ?>⭐</div>
            <div class="stat-label">Your Rating</div>
        </div>
    </div>

    <!-- Active Tasks Section -->
    <?php if(!empty($activeTasks)): ?>
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fa-solid fa-list-check"></i>
                Your Active Tasks
            </h2>
            <a href="sp-my-jobs.php" class="view-all">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="task-grid">
            <?php foreach(array_slice($activeTasks, 0, 3) as $task): ?>
                <div class="task-card">
                    <div class="task-header">
                        <div>
                            <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                        </div>
                        <div class="task-budget">₹<?php echo number_format($task['budget']); ?></div>
                    </div>
                    
                    <div class="task-meta">
                        <div class="task-meta-item">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?></span>
                        </div>
                        <div class="task-meta-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <span><?php echo htmlspecialchars($task['category']); ?></span>
                        </div>
                    </div>
                    
                    <div class="task-description">
                        <?php echo htmlspecialchars(substr($task['description'], 0, 120)) . '...'; ?>
                    </div>
                    
                    <div class="task-tags">
                        <span class="tag difficulty" style="--diff-color: <?php echo getDifficultyColor($task['difficulty']); ?>">
                            <?php echo htmlspecialchars($task['difficulty']); ?>
                        </span>
                        <span class="tag">In Progress</span>
                    </div>
                    
                    <div class="task-footer">
                        <div class="student-info">
                            <?php echo displayAvatar($task['student_pic'] ?? '', $task['student_name'], 35); ?>
                            <span class="student-name"><?php echo htmlspecialchars($task['student_name']); ?></span>
                        </div>
                        <button class="btn-accept" onclick="window.location.href='sp-job-detail.php?id=<?php echo $task['id']; ?>'">
                            <i class="fa-solid fa-eye"></i>
                            View Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Available Tasks Section -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">
                <i class="fa-solid fa-fire"></i>
                Available Tasks
            </h2>
            <a href="sp-tasks.php" class="view-all">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        
        <?php if(!empty($proposals)): ?>
            <div class="task-grid">
                <?php foreach(array_slice($proposals, 0, 6) as $proposal): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <h3 class="task-title"><?php echo htmlspecialchars($proposal['title']); ?></h3>
                            </div>
                            <div class="task-budget">₹<?php echo number_format($proposal['budget']); ?></div>
                        </div>
                        
                        <div class="task-meta">
                            <div class="task-meta-item">
                                <i class="fa-solid fa-calendar-day"></i>
                                <span>Due: <?php echo date('M d, Y', strtotime($proposal['deadline'])); ?></span>
                            </div>
                            <div class="task-meta-item">
                                <i class="fa-solid fa-layer-group"></i>
                                <span><?php echo htmlspecialchars($proposal['category']); ?></span>
                            </div>
                            <div class="task-meta-item">
                                <i class="fa-solid fa-clock"></i>
                                <span><?php echo htmlspecialchars($proposal['duration']); ?></span>
                            </div>
                        </div>
                        
                        <div class="task-description">
                            <?php echo htmlspecialchars(substr($proposal['description'], 0, 150)) . '...'; ?>
                        </div>
                        
                        <div class="task-tags">
                            <span class="tag difficulty" style="--diff-color: <?php echo getDifficultyColor($proposal['difficulty']); ?>">
                                <?php echo htmlspecialchars($proposal['difficulty']); ?>
                            </span>
                            <span class="tag">
                                <i class="fa-solid <?php echo getCategoryIcon($proposal['category']); ?>"></i>
                                <?php echo htmlspecialchars($proposal['category']); ?>
                            </span>
                        </div>
                        
                        <div class="task-footer">
                            <div class="student-info">
                                <?php echo displayAvatar($proposal['student_pic'] ?? '', $proposal['student_name'], 35); ?>
                                <span class="student-name"><?php echo htmlspecialchars($proposal['student_name']); ?></span>
                            </div>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                <button type="submit" name="accept_proposal" class="btn-accept">
                                    <i class="fa-solid fa-handshake"></i>
                                    Accept Task
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <h3>No Available Tasks</h3>
                <p>Check back later for new opportunities!</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Confirm before accepting task
document.querySelectorAll('form[method="POST"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        if(this.querySelector('[name="accept_proposal"]')) {
            if(!confirm('Are you sure you want to accept this task? You will be committed to completing it by the deadline.')) {
                e.preventDefault();
            }
        }
    });
});
</script>

</body>
</html>