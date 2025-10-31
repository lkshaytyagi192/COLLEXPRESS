<?php
session_start();
require_once 'db.php';

$spId = intval($_GET['id'] ?? 0);

if($spId == 0){
    header('Location: index.html');
    exit;
}

// Fetch SP details
$spQuery = mysqli_query($conn, "SELECT * FROM service_providers WHERE id='$spId'");
if(mysqli_num_rows($spQuery) == 0){
    die("Service provider not found");
}

$sp = mysqli_fetch_assoc($spQuery);

// Fetch statistics
$statsQuery = mysqli_query($conn, "SELECT * FROM sp_statistics WHERE sp_id='$spId'");
$stats = mysqli_fetch_assoc($statsQuery);

// Fetch portfolio
$portfolioQuery = mysqli_query($conn, "
    SELECT * FROM sp_portfolio 
    WHERE sp_id='$spId' 
    ORDER BY completion_date DESC 
    LIMIT 10
");
$portfolio = [];
while($row = mysqli_fetch_assoc($portfolioQuery)){
    $portfolio[] = $row;
}

// Fetch ratings and reviews
$ratingsQuery = mysqli_query($conn, "
    SELECT r.*, u.full_name as user_name 
    FROM ratings r
    JOIN users u ON u.id = r.user_id
    WHERE r.sp_id='$spId' AND r.rated_by='user'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$ratings = [];
while($row = mysqli_fetch_assoc($ratingsQuery)){
    $ratings[] = $row;
}

// Fetch category breakdown
$categoryQuery = mysqli_query($conn, "
    SELECT * FROM category_statistics 
    WHERE sp_id='$spId' 
    ORDER BY total_earnings DESC
");
$categories = [];
while($row = mysqli_fetch_assoc($categoryQuery)){
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sp['full_name']) ?> - Service Provider Profile</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 10px; margin-bottom: 30px; }
        .profile-info { display: flex; align-items: center; gap: 30px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: bold; color: #667eea; }
        .profile-details h1 { font-size: 32px; margin-bottom: 10px; }
        .rating-display { display: flex; align-items: center; gap: 10px; font-size: 24px; }
        .star-rating { color: #ffc107; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .stat-label { color: #666; font-size: 14px; }
        .section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section-title { font-size: 24px; font-weight: 600; margin-bottom: 20px; color: #2c3e50; }
        .portfolio-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .portfolio-item { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; }
        .review-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; }
        .review-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .category-badge { display: inline-block; padding: 8px 15px; background: #e3f2fd; color: #1976d2; border-radius: 20px; margin: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?= strtoupper(substr($sp['full_name'], 0, 2)) ?>
                </div>
                <div class="profile-details">
                    <h1><?= htmlspecialchars($sp['full_name']) ?></h1>
                    <div class="rating-display">
                        <span class="star-rating">
                            <?php for($i=0; $i<5; $i++): ?>
                                <?= $i < round($sp['average_rating']) ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </span>
                        <span><?= number_format($sp['average_rating'], 1) ?> (<?= $sp['total_ratings'] ?> reviews)</span>
                    </div>
                    <p style="margin-top: 15px; opacity: 0.9;"><?= htmlspecialchars($sp['bio'] ?? 'Professional service provider') ?></p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">₹<?= number_format($stats['total_earnings'] ?? 0) ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_jobs_completed'] ?? 0 ?></div>
                <div class="stat-label">Jobs Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($sp['average_rating'], 1) ?>⭐</div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_jobs_active'] ?? 0 ?></div>
                <div class="stat-label">Active Jobs</div>
            </div>
        </div>

        <!-- Skills & Categories -->
        <?php if(!empty($categories)): ?>
        <div class="section">
            <h2 class="section-title">📊 Service Categories</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Jobs Completed</th>
                        <th>Total Earnings</th>
                        <th>Avg Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $cat): ?>
                    <tr>
                        <td><strong><?= ucfirst($cat['category']) ?></strong></td>
                        <td><?= $cat['total_jobs'] ?></td>
                        <td>₹<?= number_format($cat['total_earnings'], 2) ?></td>
                        <td><?= number_format($cat['average_rating'], 1) ?>⭐</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Portfolio -->
        <?php if(!empty($portfolio)): ?>
        <div class="section">
            <h2 class="section-title">💼 Recent Work Portfolio</h2>
            <div class="portfolio-grid">
                <?php foreach($portfolio as $item): ?>
                <div class="portfolio-item">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span class="category-badge"><?= ucfirst($item['service_category']) ?></span>
                        <strong style="color: #667eea;">₹<?= number_format($item['amount_earned'], 2) ?></strong>
                    </div>
                    <h3 style="margin-bottom: 10px; color: #2c3e50;"><?= htmlspecialchars($item['service_title']) ?></h3>
                    <div style="color: #666; font-size: 14px;">
                        Completed: <?= date('M d, Y', strtotime($item['completion_date'])) ?>
                    </div>
                    <?php if($item['rating_received']): ?>
                    <div style="margin-top: 10px; color: #ffc107;">
                        Rating: <?= $item['rating_received'] ?>⭐
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews -->
        <?php if(!empty($ratings)): ?>
        <div class="section">
            <h2 class="section-title">⭐ Customer Reviews</h2>
            <?php foreach($ratings as $rating): ?>
            <div class="review-card">
                <div class="review-header">
                    <strong><?= htmlspecialchars($rating['user_name']) ?></strong>
                    <span style="color: #ffc107;">
                        <?php for($i=0; $i<5; $i++): ?>
                            <?= $i < $rating['rating'] ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </span>
                </div>
                <p style="color: #555; line-height: 1.6;"><?= htmlspecialchars($rating['review']) ?></p>
                <div style="color: #999; font-size: 13px; margin-top: 10px;">
                    <?= date('M d, Y', strtotime($rating['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
