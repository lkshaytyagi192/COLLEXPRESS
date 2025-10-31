<?php
session_start();
require_once 'db.php';

// Check if tracking_functions.php exists, if not, create dummy function
if(file_exists('tracking_functions.php')) {
    require_once 'tracking_functions.php';
} else {
    // Dummy function if file doesn't exist
    function logProposalPosted($conn, $userId, $proposalId, $amount) {
        // Basic tracking without the file
        mysqli_query($conn, "INSERT INTO user_activity_log (user_id, activity_type, proposal_id, amount, details, created_at) VALUES ('$userId', 'proposal_posted', '$proposalId', '$amount', 'New proposal posted', NOW())");
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';

// Fetch user data
$userQuery = mysqli_query($conn, "SELECT full_name, profile_picture FROM users WHERE id = '$userId'");
$userName = 'User';
if ($userQuery && mysqli_num_rows($userQuery) > 0) {
    $userData = mysqli_fetch_assoc($userQuery);
    $userName = $userData['full_name'] ?? 'User';
}

// Handle posting a new proposal
if(isset($_POST['post_proposal'])){
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $category = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $difficulty = mysqli_real_escape_string($conn, trim($_POST['difficulty'] ?? ''));
    $budget = floatval($_POST['budget'] ?? 0);
    $deadline = mysqli_real_escape_string($conn, trim($_POST['deadline'] ?? ''));
    $skills = mysqli_real_escape_string($conn, trim($_POST['skills'] ?? ''));
    
    // Validation
    $errors = [];
    if(empty($title)) $errors[] = "Title is required";
    if(empty($description)) $errors[] = "Description is required";
    if(empty($category)) $errors[] = "Category is required";
    if(empty($difficulty)) $errors[] = "Difficulty is required";
    if($budget <= 0) $errors[] = "Budget must be greater than 0";
    if(empty($deadline)) $errors[] = "Deadline is required";
    
    if(empty($errors)) {
        $query = "INSERT INTO proposals (user_id, title, description, category, difficulty, budget, deadline, skills, status, created_at) 
                  VALUES ('$userId', '$title', '$description', '$category', '$difficulty', '$budget', '$deadline', '$skills', 'open', NOW())";
        
        $insert = mysqli_query($conn, $query);
        
        if($insert){
            $proposalId = mysqli_insert_id($conn);
            
            // Track this proposal posting
            logProposalPosted($conn, $userId, $proposalId, $budget);
            
            $_SESSION['success_message'] = "Proposal posted successfully! 🎉";
            header("Location: my_proposals.php");
            exit;
        } else {
            $error = "Failed to post proposal. Error: " . mysqli_error($conn);
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Proposal - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); color: white; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .user-info { font-size: 14px; opacity: 0.9; }
        
        .sidebar-menu { flex: 1; padding: 20px 0; overflow-y: auto; }
        .menu-item { padding: 15px 20px; display: flex; align-items: center; gap: 15px; color: white; text-decoration: none; transition: all 0.3s; border-left: 4px solid transparent; }
        .menu-item:hover { background: rgba(255,255,255,0.1); border-left-color: white; }
        .menu-item.active { background: rgba(255,255,255,0.2); border-left-color: white; }
        .menu-item i { width: 20px; font-size: 18px; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: white; padding: 25px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .topbar h1 { font-size: 28px; color: #2c3e50; margin-bottom: 5px; }
        .topbar p { color: #6c757d; font-size: 14px; }
        
        .content-area { flex: 1; overflow-y: auto; padding: 30px; }
        
        .form-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); max-width: 900px; margin: 0 auto; }
        
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; font-size: 14px; }
        .form-group label i { margin-right: 8px; color: #667eea; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px; font-family: inherit; transition: all 0.3s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
        .form-group textarea { min-height: 140px; resize: vertical; line-height: 1.6; }
        .form-group input::placeholder, .form-group textarea::placeholder { color: #aaa; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .btn { padding: 16px 32px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; margin-top: 10px; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
        .btn-primary i { margin-right: 8px; }
        
        .error { background: #fee; color: #c33; padding: 18px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #c33; display: flex; align-items: center; gap: 10px; }
        .error i { font-size: 20px; }
        
        .helper-text { font-size: 13px; color: #6c757d; margin-top: 5px; }
        
        .form-section { margin-bottom: 35px; padding-bottom: 25px; border-bottom: 2px solid #f0f0f0; }
        .form-section:last-child { border-bottom: none; }
        .section-title { font-size: 18px; color: #2c3e50; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: #667eea; }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .form-row { grid-template-columns: 1fr; }
            .content-area { padding: 20px; }
            .form-card { padding: 25px; }
        }
    </style>
</head>
<body>
    <!-- Left Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">🎓 CollExpress</div>
            <div class="user-info">Welcome, <?= htmlspecialchars($userName) ?>!</div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fa-solid fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="my_proposals.php" class="menu-item">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>My Proposals</span>
            </a>
            <a href="postproposal.php" class="menu-item active">
                <i class="fa-solid fa-plus-circle"></i>
                <span>Post New Proposal</span>
            </a>
            <a href="my_orders.php" class="menu-item">
                <i class="fa-solid fa-shopping-bag"></i>
                <span>My Orders</span>
            </a>
            <a href="messages.php" class="menu-item">
                <i class="fa-solid fa-envelope"></i>
                <span>Messages</span>
            </a>
            <a href="profile.php" class="menu-item">
                <i class="fa-solid fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </nav>
        
        <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="logout.php" class="menu-item" style="color: #ffcccc;">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <h1>📝 Post a New Proposal</h1>
            <p>Describe the service you need and connect with qualified service providers</p>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if(!empty($error)): ?>
                <div class="error">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="postproposal.php">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fa-solid fa-info-circle"></i>
                            Basic Information
                        </div>
                        
                        <div class="form-group">
                            <label for="title">
                                <i class="fa-solid fa-heading"></i> Proposal Title *
                            </label>
                            <input type="text" id="title" name="title" placeholder="E.g., Need help with Math Assignment" required>
                            <div class="helper-text">Make it clear and specific</div>
                        </div>

                        <div class="form-group">
                            <label for="description">
                                <i class="fa-solid fa-align-left"></i> Detailed Description *
                            </label>
                            <textarea id="description" name="description" placeholder="Provide detailed information about what you need, your requirements, expectations, and any specific instructions..." required></textarea>
                            <div class="helper-text">Be as detailed as possible to attract the right service providers</div>
                        </div>
                    </div>

                    <!-- Category & Difficulty -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fa-solid fa-layer-group"></i>
                            Category & Level
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">
                                    <i class="fa-solid fa-folder"></i> Category *
                                </label>
                                <select id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="tutoring">📚 Tutoring</option>
                                    <option value="assignment">📝 Assignment Help</option>
                                    <option value="project">🎯 Project Work</option>
                                    <option value="programming">💻 Programming</option>
                                    <option value="writing">✍️ Writing</option>
                                    <option value="design">🎨 Design</option>
                                    <option value="other">📌 Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="difficulty">
                                    <i class="fa-solid fa-signal"></i> Difficulty Level *
                                </label>
                                <select id="difficulty" name="difficulty" required>
                                    <option value="">Select Difficulty</option>
                                    <option value="beginner">🟢 Beginner</option>
                                    <option value="intermediate">🟡 Intermediate</option>
                                    <option value="advanced">🔴 Advanced</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="skills">
                                <i class="fa-solid fa-lightbulb"></i> Required Skills (Optional)
                            </label>
                            <input type="text" id="skills" name="skills" placeholder="E.g., Python, Data Analysis, Excel, Mathematics">
                            <div class="helper-text">List any specific skills or tools required</div>
                        </div>
                    </div>

                    <!-- Budget & Timeline -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fa-solid fa-wallet"></i>
                            Budget & Timeline
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="budget">
                                    <i class="fa-solid fa-indian-rupee-sign"></i> Budget (₹) *
                                </label>
                                <input type="number" id="budget" name="budget" placeholder="1000" min="100" step="50" required>
                                <div class="helper-text">Minimum ₹100</div>
                            </div>

                            <div class="form-group">
                                <label for="deadline">
                                    <i class="fa-solid fa-calendar"></i> Deadline *
                                </label>
                                <input type="date" id="deadline" name="deadline" min="<?= date('Y-m-d') ?>" required>
                                <div class="helper-text">When do you need this completed?</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="post_proposal" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i> Post Proposal
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
