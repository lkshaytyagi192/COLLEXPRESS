<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php'; 
if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];

// Fetch SP profile picture
$spProfilePicture = '';
$spQuery = mysqli_query($conn, "SELECT profile_picture, full_name FROM service_providers WHERE id = '$spId'");
if($spQuery && mysqli_num_rows($spQuery) > 0) {
    $spData = mysqli_fetch_assoc($spQuery);
    $spProfilePicture = $spData['profile_picture'] ?? '';
    $spName = $spData['full_name'] ?? '';
}

// Check for messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// --- Fetch SP's Active Tasks from BOOKINGS table ---
$activeTasks = [];
$resActive = mysqli_query($conn, "
    SELECT b.*, p.title, p.description, p.category, p.difficulty, p.deadline, p.skills,
           u.full_name as student_name, u.profile_picture as student_pic, u.phone_number as student_phone
    FROM bookings b
    JOIN proposals p ON p.id = b.proposal_id
    JOIN users u ON u.id = b.user_id
    WHERE b.sp_id='$spId' AND b.status='active'
    ORDER BY p.deadline ASC
");
if($resActive){
    while($row = mysqli_fetch_assoc($resActive)){
        $activeTasks[] = $row;
    }
}

// --- Fetch SP's Completed Tasks from BOOKINGS table ---
$completedTasks = [];
$resCompleted = mysqli_query($conn, "
    SELECT b.*, p.title, p.description, p.category, p.difficulty, p.deadline,
           u.full_name as student_name, u.profile_picture as student_pic,
           r.rating, r.review
    FROM bookings b
    JOIN proposals p ON p.id = b.proposal_id
    JOIN users u ON u.id = b.user_id
    LEFT JOIN ratings r ON r.booking_id = b.id AND r.rated_by='user'
    WHERE b.sp_id='$spId' AND b.status='completed'
    ORDER BY b.completed_at DESC
");
if($resCompleted){
    while($row = mysqli_fetch_assoc($resCompleted)){
        $completedTasks[] = $row;
    }
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
    <title>My Jobs - Service Provider</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; margin-bottom: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab { background: white; border: 2px solid #ddd; padding: 15px 25px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; transition: all 0.3s; }
        .tab:hover { background: #f8f9fa; }
        .tab.active { background: #667eea; color: white; border-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .task-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .task-header { display: flex; align-items: center; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💼 My Jobs</h1>
            <p>Manage your active and completed tasks</p>
        </div>

        <?php if($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="switchTab('active-jobs')">
                <i class="fa-solid fa-briefcase"></i>
                Active Jobs (<?= count($activeTasks) ?>)
            </button>
            <button class="tab" onclick="switchTab('completed-jobs')">
                <i class="fa-solid fa-check-double"></i>
                Completed Jobs (<?= count($completedTasks) ?>)
            </button>
        </div>

        <!-- ACTIVE JOBS TAB -->
        <div id="active-jobs" class="tab-content active">
            <?php if(empty($activeTasks)): ?>
                <div class="empty-state">
                    <h3>No Active Jobs</h3>
                    <p>Accept a task from the "Available Tasks" page to get started!</p>
                    <a href="sp-tasks.php" class="btn btn-primary" style="margin-top: 20px;">Browse Available Tasks</a>
                </div>
            <?php else: ?>
                <?php foreach($activeTasks as $task): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <?= displayAvatar($task['student_pic'], $task['student_name'], 50) ?>
                            <div style="margin-left: 15px; flex: 1;">
                                <h3 style="margin-bottom: 5px; color: #2c3e50;"><?= htmlspecialchars($task['title']) ?></h3>
                                <p style="color: #666; margin: 3px 0;">
                                    <strong>Client:</strong> <?= htmlspecialchars($task['student_name']) ?>
                                </p>
                                <p style="color: #666; margin: 3px 0;">
                                    <strong>📞 Phone:</strong> <?= htmlspecialchars($task['student_phone']) ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 24px; color: #667eea; font-weight: bold;">
                                    ₹<?= number_format($task['budget'], 2) ?>
                                </div>
                                <div style="color: #999; font-size: 13px;">
                                    Due: <?= date('M d, Y', strtotime($task['deadline'])) ?>
                                </div>
                            </div>
                        </div>

                        <div style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px;">
                            <strong>Description:</strong>
                            <p style="margin-top: 8px; color: #555; line-height: 1.6;">
                                <?= nl2br(htmlspecialchars($task['description'])) ?>
                            </p>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                            <span style="background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 20px; font-size: 13px;">
                                <?= ucfirst($task['category']) ?>
                            </span>
                            <span style="background: #fff3e0; color: #f57c00; padding: 6px 12px; border-radius: 20px; font-size: 13px;">
                                <?= ucfirst($task['difficulty']) ?>
                            </span>
                            <?php if(!empty($task['skills'])): ?>
                                <span style="background: #e8f5e9; color: #388e3c; padding: 6px 12px; border-radius: 20px; font-size: 13px;">
                                    Skills: <?= htmlspecialchars($task['skills']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div style="display: flex; gap: 10px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                            <form method="POST" action="handle_proposal_response.php" style="display: inline;">
                                <input type="hidden" name="booking_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="mark_completed" class="btn btn-success" 
                                        onclick="return confirm('Mark this job as completed? This will add it to your portfolio and allow the client to rate you.')">
                                    ✓ Mark as Completed
                                </button>
                            </form>
                            
                            <a href="messages.php?user_id=<?= $task['user_id'] ?>" class="btn btn-primary">
                                💬 Message Client
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- COMPLETED JOBS TAB -->
        <div id="completed-jobs" class="tab-content">
            <?php if(empty($completedTasks)): ?>
                <div class="empty-state">
                    <h3>No Completed Jobs</h3>
                    <p>Once you finish a job, it will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach($completedTasks as $task): ?>
                    <div class="task-card" style="border-left: 4px solid #28a745;">
                        <div class="task-header">
                            <?= displayAvatar($task['student_pic'], $task['student_name'], 50) ?>
                            <div style="margin-left: 15px; flex: 1;">
                                <h3 style="margin-bottom: 5px; color: #2c3e50;"><?= htmlspecialchars($task['title']) ?></h3>
                                <p style="color: #666;">Client: <?= htmlspecialchars($task['student_name']) ?></p>
                                <p style="color: #28a745; font-weight: 600; margin-top: 5px;">
                                    ✓ Completed on <?= date('M d, Y', strtotime($task['completed_at'])) ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 24px; color: #28a745; font-weight: bold;">
                                    ₹<?= number_format($task['budget'], 2) ?>
                                </div>
                            </div>
                        </div>

                        <?php if($task['rating']): ?>
                            <div style="background: #fff3cd; padding: 15px; margin-top: 15px; border-radius: 8px;">
                                <strong>Client Rating:</strong>
                                <div style="color: #ffc107; font-size: 20px; margin: 5px 0;">
                                    <?php for($i=0; $i<5; $i++): ?>
                                        <?= $i < $task['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                    <span style="color: #666; font-size: 16px; margin-left: 10px;">
                                        <?= number_format($task['rating'], 1) ?>/5.0
                                    </span>
                                </div>
                                <?php if($task['review']): ?>
                                    <p style="margin-top: 10px; color: #555; font-style: italic;">
                                        "<?= htmlspecialchars($task['review']) ?>"
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="background: #f8f9fa; padding: 15px; margin-top: 15px; border-radius: 8px; color: #666;">
                                <i class="fa-solid fa-clock"></i> Waiting for client rating...
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.closest('.tab').classList.add('active');
        }
    </script>
</body>
</html>
