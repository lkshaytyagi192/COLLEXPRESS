<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php';

if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user data
$userQuery = mysqli_query($conn, "SELECT full_name, profile_picture FROM users WHERE id='$userId'");
$userData = mysqli_fetch_assoc($userQuery);
$userName = $userData['full_name'] ?? 'User';

// Get proposals
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM proposal_responses WHERE proposal_id=p.id) as response_count,
          (SELECT COUNT(*) FROM proposal_responses WHERE proposal_id=p.id AND status='pending') as pending_count
          FROM proposals p 
          WHERE p.user_id = ? 
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$proposals = [];
while($row = $result->fetch_assoc()){
    $proposals[] = $row;
}

// Handle accept/reject
if(isset($_POST['accept_response'])){
    // Your existing accept logic here
}

if(isset($_POST['reject_response'])){
    // Your existing reject logic here
}

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Proposals - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; }
        
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
        
        .sidebar-footer { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: white; padding: 20px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 28px; color: #2c3e50; }
        
        .content-area { flex: 1; overflow-y: auto; padding: 30px; }
        
        .success { background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        
        .proposal-card { background: white; padding: 25px; margin-bottom: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.3s; }
        .proposal-card:hover { transform: translateX(5px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        
        .proposal-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .proposal-title { font-size: 20px; font-weight: 600; color: #2c3e50; }
        
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-open { background: #e3f2fd; color: #1976d2; }
        .badge-assigned { background: #fff3e0; color: #f57c00; }
        .badge-completed { background: #e8f5e9; color: #388e3c; }
        
        .proposal-meta { display: flex; gap: 20px; flex-wrap: wrap; margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .meta-item { display: flex; align-items: center; gap: 8px; }
        .meta-item i { color: #667eea; }
        
        .responses-section { margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
        .response-card { background: #f8f9fa; padding: 20px; margin-top: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
        .contact-info { background: #e8f4f8; padding: 12px 15px; border-radius: 6px; margin: 12px 0; font-size: 14px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; margin-right: 10px; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { background: #667eea; color: white; }
        
        .empty-state { text-align: center; padding: 80px 20px; color: #999; }
        .empty-state i { font-size: 64px; color: #ddd; margin-bottom: 20px; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .sidebar-menu { display: none; }
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
            <a href="my_proposals.php" class="menu-item active">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>My Proposals</span>
            </a>
            <a href="postproposal.php" class="menu-item">
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
        
        <div class="sidebar-footer">
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
            <h1>📋 My Proposals</h1>
            <a href="postproposal.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Post New
            </a>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php if($success): ?>
                <div class="success">
                    <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if(empty($proposals)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard"></i>
                    <h3>No proposals posted yet</h3>
                    <p>Start by posting your first proposal to connect with service providers.</p>
                    <a href="postproposal.php" class="btn btn-primary" style="margin-top: 20px; text-decoration: none;">
                        <i class="fa-solid fa-plus"></i> Post New Proposal
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($proposals as $p): ?>
                    <div class="proposal-card">
                        <div class="proposal-header">
                            <div class="proposal-title"><?= htmlspecialchars($p['title']) ?></div>
                            <span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span>
                        </div>

                        <div class="proposal-meta">
                            <div class="meta-item">
                                <i class="fa-solid fa-rupee-sign"></i>
                                <span><strong>Budget:</strong> ₹<?= number_format($p['budget'], 2) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fa-solid fa-calendar"></i>
                                <span><strong>Deadline:</strong> <?= date('M d, Y', strtotime($p['deadline'])) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fa-solid fa-envelope"></i>
                                <span><strong>Responses:</strong> <?= $p['response_count'] ?> (<?= $p['pending_count'] ?> pending)</span>
                            </div>
                            <div class="meta-item">
                                <i class="fa-solid fa-tag"></i>
                                <span><strong>Category:</strong> <?= ucfirst($p['category']) ?></span>
                            </div>
                        </div>

                        <div style="color: #555; line-height: 1.6; margin-bottom: 15px;">
                            <?= nl2br(htmlspecialchars($p['description'])) ?>
                        </div>

                        <?php if($p['pending_count'] > 0 && $p['status'] == 'open'): ?>
                            <div class="responses-section">
                                <h3 style="color: #2c3e50; margin-bottom: 15px;">
                                    📨 Received Proposals (<?= $p['pending_count'] ?> pending)
                                </h3>
                                
                                <?php
                                $responsesQuery = mysqli_query($conn, "
                                    SELECT * FROM proposal_responses 
                                    WHERE proposal_id='{$p['id']}' AND status='pending'
                                    ORDER BY created_at DESC
                                ");
                                
                                while($response = mysqli_fetch_assoc($responsesQuery)):
                                ?>
                                    <div class="response-card">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                            <div style="font-size: 18px; font-weight: 600; color: #2c3e50;">
                                                👤 <?= htmlspecialchars($response['sp_name']) ?>
                                            </div>
                                            <div style="font-size: 22px; font-weight: bold; color: #667eea;">
                                                ₹<?= number_format($response['proposed_budget'], 2) ?>
                                            </div>
                                        </div>

                                        <div class="contact-info">
                                            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($response['sp_phone']) ?> | 
                                            <i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($response['sp_email']) ?>
                                        </div>

                                        <div style="margin: 15px 0; color: #555; line-height: 1.6;">
                                            <strong style="color: #2c3e50;">Cover Letter:</strong><br>
                                            <?= nl2br(htmlspecialchars($response['cover_letter'])) ?>
                                        </div>

                                        <div style="font-size: 13px; color: #999; margin-bottom: 15px;">
                                            <i class="fa-solid fa-clock"></i> Submitted on <?= date('M d, Y H:i', strtotime($response['created_at'])) ?>
                                        </div>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="response_id" value="<?= $response['id'] ?>">
                                            <button type="submit" name="accept_response" class="btn btn-success" 
                                                    onclick="return confirm('Accept this proposal? Contact information will be shared.')">
                                                <i class="fa-solid fa-check"></i> Accept & Share Contact
                                            </button>
                                            <button type="submit" name="reject_response" class="btn btn-danger"
                                                    onclick="return confirm('Reject this proposal?')">
                                                <i class="fa-solid fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                       <?php elseif($p['status'] == 'assigned'): ?>
    <?php
    // Fetch assigned service provider details
    $spQuery = mysqli_query($conn, "
        SELECT sp.full_name, sp.phone_number, sp.email, sp.average_rating, sp.total_jobs_completed
        FROM service_providers sp
        WHERE sp.id = '{$p['assigned_sp_id']}'
    ");
    
    if($spQuery && mysqli_num_rows($spQuery) > 0) {
        $spDetails = mysqli_fetch_assoc($spQuery);
    ?>
        <div class="responses-section">
            <h3 style="color: #2c3e50; margin-bottom: 15px;">
                <i class="fa-solid fa-user-check"></i> Assigned Service Provider
            </h3>
            
            <div style="background: linear-gradient(135deg, #e8f5e9 0%, #d4edda 100%); padding: 25px; border-radius: 12px; border-left: 4px solid #28a745;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 22px; font-weight: 700; color: #2c3e50; margin-bottom: 8px;">
                            👤 <?= htmlspecialchars($spDetails['full_name']) ?>
                        </div>
                        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 10px;">
                            <?php if($spDetails['average_rating'] > 0): ?>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="color: #ffc107; font-size: 18px;">
                                    <?php for($i=0; $i<5; $i++): ?>
                                        <?= $i < round($spDetails['average_rating']) ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </span>
                                <span style="color: #666; font-weight: 600;">
                                    <?= number_format($spDetails['average_rating'], 1) ?>/5.0
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="background: white; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #28a745;">
                                ✓ <?= $spDetails['total_jobs_completed'] ?> Jobs Completed
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="color: #28a745; font-size: 14px; font-weight: 600; margin-bottom: 5px;">
                            <i class="fa-solid fa-check-circle"></i> ASSIGNED
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 15px;">
                    <div style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 15px;">
                        <i class="fa-solid fa-address-card"></i> Contact Information
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="width: 40px; height: 40px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Phone Number</div>
                                <div style="font-weight: 600; color: #2c3e50;">
                                    <a href="tel:<?= htmlspecialchars($spDetails['phone_number']) ?>" style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($spDetails['phone_number']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="width: 40px; height: 40px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 3px;">Email Address</div>
                                <div style="font-weight: 600; color: #2c3e50; word-break: break-all;">
                                    <a href="mailto:<?= htmlspecialchars($spDetails['email']) ?>" style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($spDetails['email']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap;">
                    <a href="tel:<?= htmlspecialchars($spDetails['phone_number']) ?>" 
                       style="background: #28a745; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;">
                        <i class="fa-solid fa-phone"></i> Call Now
                    </a>
                    
                    <a href="mailto:<?= htmlspecialchars($spDetails['email']) ?>" 
                       style="background: #667eea; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;">
                        <i class="fa-solid fa-envelope"></i> Send Email
                    </a>
                    
                    <a href="messages.php?sp_id=<?= $p['assigned_sp_id'] ?>" 
                       style="background: #764ba2; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;">
                        <i class="fa-solid fa-comments"></i> Message
                    </a>
                </div>

                <!-- Helpful Note -->
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #ffc107;">
                    <div style="display: flex; gap: 12px; align-items: start;">
                        <i class="fa-solid fa-lightbulb" style="color: #f59e0b; font-size: 20px; margin-top: 2px;"></i>
                        <div>
                            <div style="font-weight: 600; color: #856404; margin-bottom: 5px;">Next Steps</div>
                            <div style="color: #856404; font-size: 14px; line-height: 1.6;">
                                Contact the service provider to discuss project details, timeline, and any specific requirements. 
                                Make sure to clarify all expectations before starting the work.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; color: #388e3c; margin-top: 15px;">
            <i class="fa-solid fa-check-circle"></i> This proposal has been assigned to a service provider
        </div>
    <?php } ?>
<?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
