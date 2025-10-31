<?php
session_start();
require_once 'db.php';

// Avatar helper functions
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

// Check login
if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Fetch user profile picture
$userProfilePicture = '';
$userQuery = mysqli_query($conn, "SELECT profile_picture FROM users WHERE id = '$userId'");
if($userQuery && mysqli_num_rows($userQuery) > 0) {
    $userData = mysqli_fetch_assoc($userQuery);
    $userProfilePicture = $userData['profile_picture'] ?? '';
}

// Handle form submission
if(isset($_POST['submit_learning_request'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $learning_type = mysqli_real_escape_string($conn, $_POST['learning_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $budget = floatval($_POST['budget']);
    $duration = mysqli_real_escape_string($conn, $_POST['duration']);
    $preferred_time = mysqli_real_escape_string($conn, $_POST['preferred_time']);
    
    // Insert into proposals table
    $insertQuery = "INSERT INTO proposals (user_id, title, description, category, budget, deadline, status, created_at) 
                    VALUES ('$userId', '$subject - $learning_type', '$description', 'Learning/Tutoring', '$budget', DATE_ADD(NOW(), INTERVAL 30 DAY), 'open', NOW())";
    
    if(mysqli_query($conn, $insertQuery)) {
        $success = "Learning request posted successfully! 🎉";
    } else {
        $error = "Failed to post request. Please try again.";
    }
}

// Learning subjects with unique styling
$subjects = [
    ['name' => 'Mathematics', 'emoji' => '🧮', 'color' => '#3b82f6', 'darkColor' => '#1e40af', 'icon' => 'fa-calculator'],
    ['name' => 'Physics', 'emoji' => '⚡', 'color' => '#8b5cf6', 'darkColor' => '#7c3aed', 'icon' => 'fa-atom'],
    ['name' => 'Chemistry', 'emoji' => '🧪', 'color' => '#10b981', 'darkColor' => '#059669', 'icon' => 'fa-flask'],
    ['name' => 'Computer Science', 'emoji' => '💻', 'color' => '#f59e0b', 'darkColor' => '#d97706', 'icon' => 'fa-code'],
    ['name' => 'English', 'emoji' => '📚', 'color' => '#ef4444', 'darkColor' => '#dc2626', 'icon' => 'fa-book-open'],
    ['name' => 'Biology', 'emoji' => '🌱', 'color' => '#22c55e', 'darkColor' => '#16a34a', 'icon' => 'fa-dna']
];

// Learning types
$learningTypes = [
    ['name' => 'One-on-One Tutorial', 'description' => 'Personal tutoring session', 'icon' => 'fa-user-graduate', 'color' => '#667eea'],
    ['name' => 'Study Group', 'description' => 'Learn together with peers', 'icon' => 'fa-users', 'color' => '#06b6d4'],
    ['name' => 'Quick Help', 'description' => 'Get help with specific problems', 'icon' => 'fa-bolt', 'color' => '#f59e0b'],
    ['name' => 'Exam Preparation', 'description' => 'Intensive exam coaching', 'icon' => 'fa-trophy', 'color' => '#10b981']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Learn Something - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body { background:#f4f6fb; color:#333; }
.sidebar { width:250px; background:linear-gradient(135deg,#667eea,#764ba2); min-height:100vh; padding:2rem 1rem; color:white; position:fixed; }
.sidebar h2 { text-align:center; margin-bottom:2rem; }
.sidebar a { display:flex; align-items:center; gap:1rem; padding:0.8rem 1rem; text-decoration:none; color:white; border-radius:10px; margin-bottom:0.8rem; transition: all 0.3s ease; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
.main { margin-left:250px; padding:2rem; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; background:white; padding:1.5rem; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
.topbar h1 { color:#1f2937; font-size:1.5rem; }
.profile { display:flex; align-items:center; gap:1rem; cursor:pointer; padding:0.5rem; border-radius:10px; transition: all 0.3s ease; }
.profile:hover { background:#f8fafc; }
.profile span { font-weight:600; color:#374151; }

/* Alert messages */
.alert { padding:1rem; margin-bottom:1.5rem; border-radius:12px; text-align:center; }
.alert.success { background:#d1fae5; color:#059669; border:1px solid #a7f3d0; }
.alert.error { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }

/* Section styling */
.section { margin-bottom:3rem; }
.section-title { font-size:1.8rem; font-weight:700; margin-bottom:1.5rem; color:#1f2937; display:flex; align-items:center; gap:0.5rem; }
.section-subtitle { font-size:1rem; color:#6b7280; margin-bottom:2rem; }

/* Step indicator */
.step-indicator { display:flex; justify-content:center; gap:1rem; margin-bottom:3rem; }
.step { display:flex; align-items:center; gap:0.5rem; padding:0.5rem 1.5rem; border-radius:25px; background:#e5e7eb; color:#6b7280; font-weight:600; }
.step.active { background:#667eea; color:white; }

/* Subject cards */
.subjects-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.5rem; margin-bottom:3rem; }
.subject-card { 
    background:white; 
    border-radius:20px; 
    padding:2rem; 
    cursor:pointer; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
    position:relative; 
    overflow:hidden;
    border:3px solid transparent;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.subject-card:before {
    content:'';
    position:absolute;
    top:0; left:0; right:0; bottom:0;
    background: linear-gradient(135deg, var(--card-color), var(--card-dark-color));
    opacity:0;
    transition: opacity 0.3s ease;
    z-index:1;
}

.subject-card:hover:before { opacity:1; }
.subject-card.selected { border-color: var(--card-color); box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
.subject-card.selected:before { opacity:0.15; }

.subject-card:hover { transform: translateY(-10px) scale(1.05); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
.subject-card:hover .subject-content { color:white; z-index:2; position:relative; }
.subject-card.selected .subject-content { z-index:2; position:relative; }
.subject-card:hover .subject-emoji { transform: scale(1.2) rotate(10deg); }

.subject-content { text-align:center; transition: all 0.3s ease; z-index:2; position:relative; }
.subject-emoji { font-size:3.5rem; margin-bottom:1rem; display:block; transition: all 0.3s ease; }
.subject-name { font-size:1.3rem; font-weight:700; margin-bottom:0.5rem; }
.subject-icon { font-size:1rem; opacity:0.7; }

/* Learning types */
.types-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:1.5rem; margin-bottom:3rem; }
.type-card { 
    background:white; 
    border-radius:15px; 
    padding:2rem; 
    cursor:pointer; 
    transition: all 0.3s ease; 
    border-left:5px solid var(--type-color);
    position:relative;
    overflow:hidden;
}

.type-card.selected {
    border-left:5px solid #667eea;
    background:linear-gradient(135deg, rgba(102,126,234,0.1), rgba(255,255,255,0.9));
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.type-card:hover { 
    transform: translateX(10px) translateY(-5px); 
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15); 
}

.type-card:before {
    content:'';
    position:absolute;
    top:0; left:0;
    width:5px; height:100%;
    background: var(--type-color);
    transition: width 0.3s ease;
}

.type-card:hover:before { width:100%; opacity:0.1; }

.type-header { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; }
.type-icon { 
    width:50px; height:50px; 
    background: var(--type-color); 
    border-radius:12px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    color:white; 
    font-size:1.2rem;
    transition: all 0.3s ease;
}
.type-card:hover .type-icon { transform: rotate(5deg) scale(1.1); }
.type-name { font-size:1.2rem; font-weight:700; color:#1f2937; }
.type-description { color:#6b7280; font-size:0.95rem; line-height:1.5; }

/* Form section */
.form-section { display:none; background:white; padding:2rem; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
.form-section.active { display:block; }

.form-group { margin-bottom:1.5rem; }
.form-group label { display:block; font-weight:600; margin-bottom:0.5rem; color:#374151; }
.form-group input, .form-group textarea { width:100%; padding:0.8rem; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem; }
.form-group input:focus, .form-group textarea:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
.form-group textarea { resize:vertical; min-height:100px; }

/* Floating action button */
.fab { 
    position:fixed; 
    bottom:2rem; right:2rem; 
    width:60px; height:60px; 
    background:linear-gradient(135deg,#667eea,#764ba2); 
    border-radius:50%; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    color:white; 
    font-size:1.5rem; 
    cursor:pointer; 
    box-shadow: 0 8px 25px rgba(102,126,234,0.4);
    transition: all 0.3s ease;
    z-index:1000;
    border:none;
}
.fab:hover { transform: scale(1.1) rotate(10deg); box-shadow: 0 12px 35px rgba(102,126,234,0.6); }
.fab.ready { background:linear-gradient(135deg,#10b981,#059669); }

.btn { padding:0.8rem 2rem; background:#667eea; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; transition:all 0.3s ease; }
.btn:hover { background:#5a67d8; transform:translateY(-2px); }

@media (max-width:768px) { 
    .sidebar { width:200px; } 
    .main { margin-left:200px; padding:1rem; } 
    .topbar { flex-direction:column; gap:1rem; text-align:center; }
    .subjects-grid, .types-grid { grid-template-columns: 1fr; }
    .fab { bottom:1rem; right:1rem; width:50px; height:50px; font-size:1.2rem; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>🎓 CollExpress</h2>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
    <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Learn Something 📚✨</h1>
        <div class="profile" onclick="window.location.href='profile.php'">
            <?php echo displayAvatar($userProfilePicture, $userName, 40); ?>
            <span><?php echo htmlspecialchars($userName); ?></span>
        </div>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" id="step1-indicator">
            <i class="fa-solid fa-1"></i>
            <span>Choose Subject</span>
        </div>
        <div class="step" id="step2-indicator">
            <i class="fa-solid fa-2"></i>
            <span>Learning Type</span>
        </div>
        <div class="step" id="step3-indicator">
            <i class="fa-solid fa-3"></i>
            <span>Details</span>
        </div>
    </div>

    <!-- Step 1: Subjects Section -->
    <div class="section" id="step1">
        <h2 class="section-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Choose Your Subject
        </h2>
        <p class="section-subtitle">Select the subject you need help with</p>
        
        <div class="subjects-grid">
            <?php foreach($subjects as $subject): ?>
                <div class="subject-card" 
                     style="--card-color: <?php echo $subject['color']; ?>; --card-dark-color: <?php echo $subject['darkColor']; ?>;"
                     onclick="selectSubject('<?php echo $subject['name']; ?>', this)">
                    <div class="subject-content">
                        <span class="subject-emoji"><?php echo $subject['emoji']; ?></span>
                        <div class="subject-name"><?php echo $subject['name']; ?></div>
                        <i class="fa-solid <?php echo $subject['icon']; ?> subject-icon"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 2: Learning Types Section -->
    <div class="section" id="step2" style="display:none;">
        <h2 class="section-title">
            <i class="fa-solid fa-chalkboard-teacher"></i>
            How Do You Want to Learn?
        </h2>
        <p class="section-subtitle">Choose your preferred learning method</p>
        
        <div class="types-grid">
            <?php foreach($learningTypes as $type): ?>
                <div class="type-card" 
                     style="--type-color: <?php echo $type['color']; ?>;"
                     onclick="selectLearningType('<?php echo $type['name']; ?>', this)">
                    <div class="type-header">
                        <div class="type-icon">
                            <i class="fa-solid <?php echo $type['icon']; ?>"></i>
                        </div>
                        <div class="type-name"><?php echo $type['name']; ?></div>
                    </div>
                    <div class="type-description"><?php echo $type['description']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 3: Details Form -->
    <div class="section" id="step3" style="display:none;">
        <h2 class="section-title">
            <i class="fa-solid fa-file-lines"></i>
            Learning Request Details
        </h2>
        <p class="section-subtitle">Provide additional information about your learning needs</p>
        
        <div class="form-section active">
            <form method="post">
                <input type="hidden" name="subject" id="selectedSubject">
                <input type="hidden" name="learning_type" id="selectedLearningType">
                
                <div class="form-group">
                    <label>📝 Describe what you want to learn</label>
                    <textarea name="description" required placeholder="Example: I need help understanding calculus derivatives and integration for my upcoming exam..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>💰 Budget (₹)</label>
                    <input type="number" name="budget" required placeholder="500" min="1">
                </div>
                
                <div class="form-group">
                    <label>⏱️ Duration</label>
                    <input type="text" name="duration" required placeholder="e.g., 2 hours, 1 week, ongoing">
                </div>
                
                <div class="form-group">
                    <label>🕐 Preferred Time</label>
                    <input type="text" name="preferred_time" required placeholder="e.g., Weekday evenings, Weekend mornings">
                </div>
                
                <button type="submit" name="submit_learning_request" class="btn">
                    <i class="fa-solid fa-paper-plane"></i> Post Learning Request
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" id="fabButton" onclick="handleFabClick()" title="Next Step">
        <i class="fa-solid fa-arrow-right"></i>
    </button>
</div>

<script>
let selectedSubject = '';
let selectedLearningType = '';
let currentStep = 1;

function selectSubject(subject, element) {
    selectedSubject = subject;
    document.getElementById('selectedSubject').value = subject;
    
    // Remove previous selection
    document.querySelectorAll('.subject-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    element.classList.add('selected');
    
    // Update FAB
    updateFabButton();
    
    console.log('Selected subject:', subject);
}

function selectLearningType(type, element) {
    selectedLearningType = type;
    document.getElementById('selectedLearningType').value = type;
    
    // Remove previous selection
    document.querySelectorAll('.type-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    element.classList.add('selected');
    
    // Update FAB
    updateFabButton();
    
    console.log('Selected learning type:', type);
}

function updateFabButton() {
    const fab = document.querySelector('.fab');
    
    if(currentStep === 1 && selectedSubject) {
        fab.classList.add('ready');
    } else if(currentStep === 2 && selectedLearningType) {
        fab.classList.add('ready');
    } else {
        fab.classList.remove('ready');
    }
}

function handleFabClick() {
    if(currentStep === 1) {
        if(!selectedSubject) {
            alert('Please select a subject first!');
            return;
        }
        // Move to step 2
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'block';
        document.getElementById('step1-indicator').classList.remove('active');
        document.getElementById('step2-indicator').classList.add('active');
        currentStep = 2;
        document.querySelector('.fab').classList.remove('ready');
        console.log('Moved to step 2');
    } else if(currentStep === 2) {
        if(!selectedLearningType) {
            alert('Please select a learning type first!');
            return;
        }
        // Move to step 3
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step3').style.display = 'block';
        document.getElementById('step2-indicator').classList.remove('active');
        document.getElementById('step3-indicator').classList.add('active');
        document.querySelector('.fab').style.display = 'none'; // Hide FAB on form page
        currentStep = 3;
        console.log('Moved to step 3');
    }
}

// Add some interactive effects
document.querySelectorAll('.subject-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        if(!this.classList.contains('selected')) {
            this.style.transform = 'translateY(-10px) scale(1.05)';
        }
    });
    
    card.addEventListener('mouseleave', function() {
        if(!this.classList.contains('selected')) {
            this.style.transform = 'translateY(0) scale(1)';
        }
    });
});

document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        if(!this.classList.contains('selected')) {
            this.style.transform = 'translateX(10px) translateY(-5px)';
        }
    });
    
    card.addEventListener('mouseleave', function() {
        if(!this.classList.contains('selected')) {
            this.style.transform = 'translateY(0) translateX(0)';
        }
    });
});
</script>

</body>
</html>