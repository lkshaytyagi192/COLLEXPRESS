<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];
$spName = $_SESSION['sp_full_name'];
$error = '';

// Fetch SP data
$spQuery = mysqli_query($conn, "SELECT * FROM service_providers WHERE id='$spId'");
if($spQuery && mysqli_num_rows($spQuery) > 0) {
    $spData = mysqli_fetch_assoc($spQuery);
} else {
    $spData = [
        'full_name' => $spName,
        'email' => '',
        'phone_number' => '',
        'bio' => '',
        'skills' => '',
        'profile_picture' => ''
    ];
}

// Handle profile update
if(isset($_POST['update_profile'])){
    $fullName = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $skills = mysqli_real_escape_string($conn, trim($_POST['skills']));
    
    $update = mysqli_query($conn, "
        UPDATE service_providers 
        SET full_name='$fullName', 
            email='$email', 
            phone_number='$phone', 
            bio='$bio', 
            skills='$skills' 
        WHERE id='$spId'
    ");
    
    if($update){
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: sp-profile.php");
        exit;
    } else {
        $error = "Failed to update profile: " . mysqli_error($conn);
    }
}

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 25px; margin-bottom: 25px; border-radius: 8px; display: flex; align-items: center; gap: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .form-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .btn-primary { background: #667eea; color: white; width: 100%; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>👤 My Profile</h1>
            <p>Manage your account information</p>
        </div>
<div style="margin-bottom: 20px;">
    <a href="sp-dashboard.php" style="background: #667eea; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

        <!-- Phone Warning -->
        <?php if(empty($spData['phone_number']) || isset($_GET['phone_required'])): ?>
            <div class="warning">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 40px; color: #f59e0b;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #856404; font-size: 20px; margin-bottom: 8px;">
                        📞 Phone Number Required
                    </div>
                    <div style="color: #856404; line-height: 1.6; margin-bottom: 10px;">
                        You cannot view or accept tasks until you add a phone number! Clients need a way to contact you directly.
                    </div>
                    <div style="background: white; padding: 12px; border-radius: 6px;">
                        <strong>Why is this important?</strong> When clients accept your proposal, they need to reach you immediately to discuss project details and timelines.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($spData['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($spData['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>📞 Phone Number (Required for accepting tasks)</label>
                    <input type="tel" name="phone_number" value="<?= htmlspecialchars($spData['phone_number']) ?>" placeholder="+91 9876543210" required>
                    <small style="color: #666;">This will be shared with clients when they accept your proposal</small>
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Tell clients about yourself and your experience..."><?= htmlspecialchars($spData['bio']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Skills</label>
                    <input type="text" name="skills" value="<?= htmlspecialchars($spData['skills']) ?>" placeholder="Python, JavaScript, Design, etc.">
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</body>
</html>
