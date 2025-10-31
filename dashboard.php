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
                        style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">';
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
                                border: 2px solid #667eea;">
                        ' . $initials . '
                    </div>';
        }
    }

    // Redirect if not logged in as student
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - CollExpress</title>
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
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 2rem;
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
        transform: translateX(5px);
    }

    .main {
        margin-left: 250px;
        padding: 2rem;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: white;
        padding: 1.5rem;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .topbar h1 {
        color: #1f2937;
        font-size: 1.5rem;
    }

    .profile {
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .profile:hover {
        background: #f8fafc;
    }

    .profile span {
        font-weight: 600;
        color: #374151;
    }

    /* Dashboard Cards */
    .dashboard-card {
        cursor: pointer;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }

    .dashboard-card h2 {
        margin-top: 1rem;
    }

    .dashboard-card p {
        margin-top: 0.5rem;
        font-size: 0.95rem;
        color: #4b5563;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }
        
        .main {
            margin-left: 200px;
            padding: 1rem;
        }
        
        .topbar {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
    }
    </style>
    </head>
    <body>

    <div class="sidebar">
        <h2>🎓 CollExpress</h2>
        <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Dashboard</a>
        <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
        <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
        <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
        <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <div class="main">
        <div class="topbar">
            <h1>Welcome, <?php echo htmlspecialchars($userName); ?> 👋</h1>
            <div class="profile" onclick="window.location.href='profile.php'">
                <?php echo displayAvatar($userProfilePicture, $userName, 40); ?>
                <span><?php echo htmlspecialchars($userName); ?></span>
            </div>
        </div>

        <!-- Dashboard Options -->
        <div class="jobs" style="display: flex; gap: 2rem; flex-wrap: wrap; justify-content: center;">
            <!-- Learn Something -->
            <div class="dashboard-card" onclick="window.location.href='learn.php'" style="flex: 1 1 250px; background: #fef3c7;">
                <i class="fa-solid fa-book" style="font-size: 2.5rem; color: #f59e0b;"></i>
                <h2 style="color: #b45309;">Learn Something</h2>
                <p>Post a request to learn a subject or chapter from other students.</p>
            </div>

            <!-- Order Something -->
            <div class="dashboard-card" onclick="window.location.href='order.php'" style="flex: 1 1 250px; background: #dbeafe;">
                <i class="fa-solid fa-utensils" style="font-size: 2.5rem; color: #1e40af;"></i>
                <h2 style="color: #1e3a8a;">Order Something</h2>
                <p>Request food, stationery, or rent electricals from the college.</p>
            </div>

            <!-- Post Proposal -->
            <div class="dashboard-card" onclick="window.location.href='postproposal.php'" style="flex: 1 1 250px; background: #d1fae5;">
                <i class="fa-solid fa-briefcase" style="font-size: 2.5rem; color: #059669;"></i>
                <h2 style="color: #047857;">Post a Proposal</h2>
                <p>Post a project proposal for other students to complete for you.</p>
            </div>
        </div>
    </div>

    </body>
    </html>
