<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle settings update
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Password change
    if(isset($_POST['change_password']) && !empty($_POST['current_password'])){
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'];
        
        $userQuery = mysqli_query($conn, "SELECT password FROM users WHERE id='$userId'");
        $userData = mysqli_fetch_assoc($userQuery);
        
        if(password_verify($currentPass, $userData['password'])){
            if($newPass === $confirmPass){
                if(strlen($newPass) >= 6){
                    $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
                    mysqli_query($conn, "UPDATE users SET password='$hashedPass' WHERE id='$userId'");
                    $success = "Password changed successfully!";
                } else {
                    $error = "New password must be at least 6 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
    
    // User settings update (WITHOUT language)
    if(isset($_POST['update_settings'])){
        $theme = mysqli_real_escape_string($conn, $_POST['theme'] ?? 'light');
        $timezone = mysqli_real_escape_string($conn, $_POST['timezone'] ?? 'Asia/Kolkata');
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
        $job_alerts = isset($_POST['job_alerts']) ? 1 : 0;
        $message_alerts = isset($_POST['message_alerts']) ? 1 : 0;
        $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
        $profile_visibility = mysqli_real_escape_string($conn, $_POST['profile_visibility'] ?? 'public');
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        
        // Insert or update user settings (WITHOUT language field)
        $sql = "INSERT INTO user_settings 
                (user_id, theme, timezone, email_notifications, sms_notifications, 
                 push_notifications, job_alerts, message_alerts, marketing_emails, 
                 profile_visibility, two_factor_auth) 
                VALUES 
                ('$userId', '$theme', '$timezone', $email_notifications, $sms_notifications,
                 $push_notifications, $job_alerts, $message_alerts, $marketing_emails,
                 '$profile_visibility', $two_factor_auth)
                ON DUPLICATE KEY UPDATE
                theme='$theme', 
                timezone='$timezone', 
                email_notifications=$email_notifications, 
                sms_notifications=$sms_notifications, 
                push_notifications=$push_notifications, 
                job_alerts=$job_alerts, 
                message_alerts=$message_alerts, 
                marketing_emails=$marketing_emails, 
                profile_visibility='$profile_visibility', 
                two_factor_auth=$two_factor_auth";
        
        if(mysqli_query($conn, $sql)){
            $success = "Settings updated successfully!";
        } else {
            $error = "Failed to update settings: " . mysqli_error($conn);
        }
    }
}

// Fetch current settings with default values
$res = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id='$userId'");
$settings = mysqli_fetch_assoc($res);

// Set default values if no settings exist
if(!$settings){
    $settings = [
        'theme' => 'light',
        'timezone' => 'Asia/Kolkata',
        'email_notifications' => 1,
        'sms_notifications' => 0,
        'push_notifications' => 1,
        'job_alerts' => 1,
        'message_alerts' => 1,
        'marketing_emails' => 0,
        'profile_visibility' => 'public',
        'two_factor_auth' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .logo { color: white; font-size: 24px; font-weight: bold; text-decoration: none; }
        .nav-links { display: flex; gap: 25px; list-style: none; flex-wrap: wrap; }
        .nav-links a { color: white; text-decoration: none; font-weight: 500; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 0.8; }
        
        .container { max-width: 900px; margin: 30px auto; padding: 20px; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        
        .settings-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .settings-section { margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee; }
        .settings-section:last-child { border-bottom: none; margin-bottom: 0; }
        .section-title { font-size: 20px; color: #2c3e50; margin-bottom: 20px; font-weight: 600; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        
        .checkbox-group { display: flex; align-items: center; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; margin-right: 10px; cursor: pointer; }
        .checkbox-group label { margin: 0; cursor: pointer; flex: 1; }
        
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; }
        .radio-item { display: flex; align-items: center; padding: 10px 20px; background: #f8f9fa; border-radius: 6px; cursor: pointer; transition: all 0.3s; }
        .radio-item:hover { background: #e9ecef; }
        .radio-item input[type="radio"] { width: 18px; height: 18px; margin-right: 8px; cursor: pointer; }
        .radio-item label { cursor: pointer; margin: 0; }
        
        .btn { padding: 12px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        
        @media (max-width: 768px) {
            .nav-links { gap: 15px; }
            .container { padding: 10px; }
            .settings-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">🎓 CollExpress</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_proposals.php">My Proposals</a></li>
                <li><a href="messages.php">Messages</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>⚙️ User Settings</h1>
            <p>Manage your account preferences and security</p>
        </div>

        <?php if($success): ?>
            <div class="success">✓ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Password Change Section -->
        <div class="settings-card">
            <div class="settings-section">
                <h2 class="section-title">🔐 Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Enter new password (min 6 characters)">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fa-solid fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

        <!-- General Settings -->
        <div class="settings-card">
            <form method="POST">
                <!-- Appearance -->
                <div class="settings-section">
                    <h2 class="section-title">🎨 Appearance & Display</h2>
                    
                    <div class="form-group">
                        <label>Theme</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="light" name="theme" value="light" 
                                       <?= $settings['theme'] == 'light' ? 'checked' : '' ?>>
                                <label for="light">☀️ Light</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="dark" name="theme" value="dark"
                                       <?= $settings['theme'] == 'dark' ? 'checked' : '' ?>>
                                <label for="dark">🌙 Dark</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Timezone</label>
                        <select name="timezone">
                            <option value="Asia/Kolkata" <?= $settings['timezone'] == 'Asia/Kolkata' ? 'selected' : '' ?>>🇮🇳 Asia/Kolkata (IST)</option>
                            <option value="America/New_York" <?= $settings['timezone'] == 'America/New_York' ? 'selected' : '' ?>>🇺🇸 America/New_York (EST)</option>
                            <option value="Europe/London" <?= $settings['timezone'] == 'Europe/London' ? 'selected' : '' ?>>🇬🇧 Europe/London (GMT)</option>
                            <option value="Asia/Dubai" <?= $settings['timezone'] == 'Asia/Dubai' ? 'selected' : '' ?>>🇦🇪 Asia/Dubai (GST)</option>
                        </select>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="settings-section">
                    <h2 class="section-title">🔔 Notification Preferences</h2>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                               <?= $settings['email_notifications'] ? 'checked' : '' ?>>
                        <label for="email_notifications">📧 Email Notifications</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="sms_notifications" name="sms_notifications"
                               <?= $settings['sms_notifications'] ? 'checked' : '' ?>>
                        <label for="sms_notifications">📱 SMS Notifications</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="push_notifications" name="push_notifications"
                               <?= $settings['push_notifications'] ? 'checked' : '' ?>>
                        <label for="push_notifications">🔔 Push Notifications</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="job_alerts" name="job_alerts"
                               <?= $settings['job_alerts'] ? 'checked' : '' ?>>
                        <label for="job_alerts">💼 Job Alerts</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="message_alerts" name="message_alerts"
                               <?= $settings['message_alerts'] ? 'checked' : '' ?>>
                        <label for="message_alerts">💬 Message Alerts</label>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="marketing_emails" name="marketing_emails"
                               <?= $settings['marketing_emails'] ? 'checked' : '' ?>>
                        <label for="marketing_emails">📢 Marketing Emails</label>
                    </div>
                </div>

                <!-- Privacy -->
                <div class="settings-section">
                    <h2 class="section-title">🔒 Privacy & Security</h2>
                    
                    <div class="form-group">
                        <label>Profile Visibility</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="public" name="profile_visibility" value="public"
                                       <?= $settings['profile_visibility'] == 'public' ? 'checked' : '' ?>>
                                <label for="public">🌍 Public</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="private" name="profile_visibility" value="private"
                                       <?= $settings['profile_visibility'] == 'private' ? 'checked' : '' ?>>
                                <label for="private">🔒 Private</label>
                            </div>
                        </div>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="two_factor_auth" name="two_factor_auth"
                               <?= $settings['two_factor_auth'] ? 'checked' : '' ?>>
                        <label for="two_factor_auth">🔐 Two-Factor Authentication (2FA)</label>
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>
</body>
</html>
