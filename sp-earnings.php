<?php
session_start();
require_once 'db.php';
require_once 'theme_config.php';

if(!isset($_SESSION['sp_id'])){
    header('Location: sp-auth.php');
    exit;
}

$spId = $_SESSION['sp_id'];

// Fetch SP data
$spQuery = mysqli_query($conn, "SELECT full_name, total_earnings, total_jobs_completed FROM service_providers WHERE id='$spId'");
$spData = mysqli_fetch_assoc($spQuery);
$spName = $spData['full_name'] ?? 'Service Provider';
$totalEarnings = $spData['total_earnings'] ?? 0;
$totalJobs = $spData['total_jobs_completed'] ?? 0;

// Fetch completed jobs from bookings
$jobsQuery = mysqli_query($conn, "
    SELECT b.title, b.budget, b.completed_at, b.category, p.difficulty
    FROM bookings b
    LEFT JOIN proposals p ON p.id = b.proposal_id
    WHERE b.sp_id='$spId' AND b.status='completed' 
    ORDER BY b.completed_at DESC
");

$jobs = [];
if($jobsQuery){
    while($row = mysqli_fetch_assoc($jobsQuery)){
        $jobs[] = $row;
    }
}

// Calculate earnings by category
$categoryQuery = mysqli_query($conn, "
    SELECT category, SUM(total_earnings) as earnings, COUNT(*) as count
    FROM category_statistics 
    WHERE sp_id='$spId'
    GROUP BY category
");

$categories = [];
if($categoryQuery){
    while($row = mysqli_fetch_assoc($categoryQuery)){
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - CollExpress SP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); color: white; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 25px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 13px; opacity: 0.9; }
        
        .sidebar a { padding: 15px 20px; display: flex; align-items: center; gap: 15px; color: white; text-decoration: none; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); border-left-color: white; }
        .sidebar a.active { background: rgba(255,255,255,0.2); border-left-color: white; }
        .sidebar a i { width: 20px; font-size: 18px; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        .topbar { background: white; padding: 25px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 28px; color: #2c3e50; margin-bottom: 5px; }
        .topbar p { color: #6c757d; font-size: 14px; }
        
        .content-area { flex: 1; overflow-y: auto; padding: 30px; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 20px; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .stat-icon.green { background: #d1fae5; color: #10b981; }
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.purple { background: #e9d5ff; color: #a855f7; }
        .stat-value { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #6c757d; font-size: 14px; margin-top: 5px; }
        
        /* Sections */
        .section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .section-title { font-size: 20px; font-weight: 600; color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* Table */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table thead { background: #f8f9fa; }
        table th { padding: 15px; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 2px solid #e5e7eb; }
        table td { padding: 15px; color: #4b5563; border-bottom: 1px solid #f0f0f0; }
        table tr:hover { background: #f8f9fa; }
        
        .badge { padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-primary { background: #e0e7ff; color: #4f46e5; }
        .badge-success { background: #d1fae5; color: #10b981; }
        .badge-warning { background: #fef3c7; color: #f59e0b; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; }
        .empty-state h3 { color: #6b7280; margin-bottom: 10px; }
        
        /* Category Grid */
        .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .category-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; }
        .category-card h4 { font-size: 16px; margin-bottom: 10px; }
        .category-card .amount { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .category-card .count { font-size: 13px; opacity: 0.9; }
        
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🛠️ CollExpress</h2>
            <p>Service Provider Portal</p>
        </div>
        <a href="sp-dashboard.php">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="sp-tasks.php">
            <i class="fa-solid fa-briefcase"></i> Available Tasks
        </a>
        <a href="sp-my-jobs.php">
            <i class="fa-solid fa-list-check"></i> My Jobs
        </a>
        <a href="sp-earnings.php" class="active">
            <i class="fa-solid fa-wallet"></i> Earnings
        </a>
        <a href="messages.php">
            <i class="fa-solid fa-envelope"></i> Messages
        </a>
        <a href="sp-profile.php">
            <i class="fa-solid fa-user-circle"></i> Profile
        </a>
        <a href="sp-logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div>
                <h1>💰 Your Earnings</h1>
                <p>Track your income and payment history</p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px;">
                    <?= strtoupper(substr($spName, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($spName) ?></div>
                    <div style="font-size: 13px; color: #6c757d;">Service Provider</div>
                </div>
            </div>
        </div>

        <div class="content-area">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                    <div>
                        <div class="stat-value">₹<?= number_format($totalEarnings, 2) ?></div>
                        <div class="stat-label">Total Earnings</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= $totalJobs ?></div>
                        <div class="stat-label">Completed Jobs</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="stat-value">₹<?= $totalJobs > 0 ? number_format($totalEarnings / $totalJobs, 2) : '0' ?></div>
                        <div class="stat-label">Average Per Job</div>
                    </div>
                </div>
            </div>

            <!-- Earnings by Category -->
            <?php if(!empty($categories)): ?>
            <div class="section">
                <h3 class="section-title">
                    <i class="fa-solid fa-chart-pie"></i> Earnings by Category
                </h3>
                <div class="category-grid">
                    <?php foreach($categories as $cat): ?>
                    <div class="category-card">
                        <h4><?= ucfirst($cat['category']) ?></h4>
                        <div class="amount">₹<?= number_format($cat['earnings'], 2) ?></div>
                        <div class="count"><?= $cat['count'] ?> jobs completed</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment History -->
            <div class="section">
                <h3 class="section-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Payment History
                </h3>
                
                <?php if(!empty($jobs)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Category</th>
                                <th>Difficulty</th>
                                <th>Amount</th>
                                <th>Completed On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($jobs as $job): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars($job['title']) ?></td>
                                <td>
                                    <span class="badge badge-primary"><?= ucfirst($job['category']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $job['difficulty'] == 'advanced' ? 'warning' : 'success' ?>">
                                        <?= ucfirst($job['difficulty']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: #10b981;">
                                    ₹<?= number_format($job['budget'], 2) ?>
                                </td>
                                <td style="color: #6c757d;">
                                    <?= date('M d, Y', strtotime($job['completed_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <h3>No Earnings Yet</h3>
                    <p>Complete tasks to start earning!</p>
                    <a href="sp-tasks.php" style="margin-top: 20px; display: inline-block; background: #667eea; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                        <i class="fa-solid fa-briefcase"></i> Browse Tasks
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
