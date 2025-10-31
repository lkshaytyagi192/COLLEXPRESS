<?php
session_start();
require_once 'db.php';

// Avatar helper functions
function getAvatarInitials($fullName) {
    $names = explode(' ', trim($fullName));
    if(count($names) >= 2) {
        return strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

function getAvatarColor($fullName) {
    $colors = ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a'];
    $index = strlen($fullName) % count($colors);
    return $colors[$index];
}

function displayAvatar($profilePicture, $fullName, $size = 40) {
    if(!empty($profilePicture) && file_exists($profilePicture)) {
        return '<img src="' . htmlspecialchars($profilePicture) . '" alt="' . htmlspecialchars($fullName) . '" style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover;">';
    } else {
        $initials = getAvatarInitials($fullName);
        $color = getAvatarColor($fullName);
        return '<div style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; background: ' . $color . '; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: ' . ($size/2.5) . 'px;">' . $initials . '</div>';
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
$userStmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
if($userStmt) {
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    if($userResult && $userResult->num_rows > 0) {
        $userData = $userResult->fetch_assoc();
        $userProfilePicture = $userData['profile_picture'] ?? '';
    }
    $userStmt->close();
}

// Fetch orders with proper error handling
$orders = [];
$ordersStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
if($ordersStmt) {
    $ordersStmt->bind_param("i", $userId);
    if($ordersStmt->execute()) {
        $ordersResult = $ordersStmt->get_result();
        if($ordersResult) {
            while($order = $ordersResult->fetch_assoc()) {
                // Fetch order items for each order
                $itemsStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                if($itemsStmt) {
                    $itemsStmt->bind_param("i", $order['id']);
                    if($itemsStmt->execute()) {
                        $itemsResult = $itemsStmt->get_result();
                        $order['items'] = [];
                        if($itemsResult) {
                            while($item = $itemsResult->fetch_assoc()) {
                                $order['items'][] = $item;
                            }
                        }
                    }
                    $itemsStmt->close();
                }
                $orders[] = $order;
            }
        }
    }
    $ordersStmt->close();
}

// Status badge colors
function getStatusBadge($status) {
    $badges = [
        'pending' => ['color' => '#f59e0b', 'icon' => 'fa-clock', 'text' => 'Pending'],
        'confirmed' => ['color' => '#3b82f6', 'icon' => 'fa-check-circle', 'text' => 'Confirmed'],
        'preparing' => ['color' => '#8b5cf6', 'icon' => 'fa-fire', 'text' => 'Preparing'],
        'out_for_delivery' => ['color' => '#06b6d4', 'icon' => 'fa-motorcycle', 'text' => 'Out for Delivery'],
        'delivered' => ['color' => '#10b981', 'icon' => 'fa-check-double', 'text' => 'Delivered'],
        'cancelled' => ['color' => '#ef4444', 'icon' => 'fa-times-circle', 'text' => 'Cancelled']
    ];
    return $badges[$status] ?? $badges['pending'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Inter', sans-serif;
    color: #1f2937;
    min-height: 100vh;
}

.sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    min-height: 100vh;
    padding: 2rem 1.5rem;
    position: fixed;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
    z-index: 100;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 3rem;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 16px;
    color: white;
}

.sidebar-logo i {
    font-size: 1.8rem;
}

.sidebar-logo h2 {
    font-size: 1.4rem;
    font-weight: 700;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    text-decoration: none;
    color: #4b5563;
    border-radius: 12px;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.sidebar a:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    color: #667eea;
    transform: translateX(8px);
}

.sidebar a.active {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    color: #667eea;
    font-weight: 600;
}

.sidebar a i {
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
}

.main {
    margin-left: 280px;
    padding: 2.5rem;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    padding: 1.75rem 2rem;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
}

.topbar-left h1 {
    color: #1f2937;
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.topbar-left p {
    color: #6b7280;
    font-size: 0.95rem;
}

.profile {
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    padding: 0.75rem 1.25rem;
    border-radius: 50px;
    transition: all 0.3s ease;
    background: rgba(102, 126, 234, 0.05);
}

.profile:hover {
    background: rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.profile-info .name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
}

.profile-info .status {
    font-size: 0.8rem;
    color: #10b981;
}

.orders-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.empty-state {
    background: white;
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.empty-state i {
    font-size: 5rem;
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-state h2 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 2rem;
}

.empty-state a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.empty-state a:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.order-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f3f4f6;
}

.order-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.order-date {
    color: #6b7280;
    font-size: 0.9rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
}

.order-items {
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 0.75rem;
}

.item-image {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.item-canteen {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.item-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.item-quantity {
    color: #6b7280;
    font-size: 0.875rem;
}

.item-total {
    font-weight: 600;
    color: #667eea;
}

.order-summary {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    color: #6b7280;
}

.summary-row.total {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    padding-top: 0.5rem;
    border-top: 2px solid #e5e7eb;
    margin-top: 0.5rem;
}

.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.delivery-address {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
    flex: 1;
}

.delivery-address i {
    margin-top: 0.25rem;
    color: #667eea;
}

.order-actions {
    display: flex;
    gap: 0.75rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f3f4f6;
    color: #667eea;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

@media (max-width: 1024px) {
    .sidebar {
        width: 240px;
    }
    .main {
        margin-left: 240px;
        padding: 2rem;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .main {
        margin-left: 0;
        padding: 1.5rem;
    }
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    .order-footer {
        flex-direction: column;
        align-items: flex-start;
    }
    .order-actions {
        width: 100%;
        flex-direction: column;
    }
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <h2>CollExpress</h2>
    </div>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
    <a href="order.php"><i class="fa-solid fa-cart-shopping"></i> Order Services</a>
    <a href="my_orders.php" class="active"><i class="fa-solid fa-box"></i> My Orders</a>
    <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>My Orders</h1>
            <p>Track and manage your orders</p>
        </div>
        <div class="profile" onclick="window.location.href='profile.php'">
            <?php echo displayAvatar($userProfilePicture, $userName, 45); ?>
            <div class="profile-info">
                <span class="name"><?php echo htmlspecialchars($userName); ?></span>
                <span class="status">Online</span>
            </div>
        </div>
    </div>

    <div class="orders-container">
        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders. Start exploring our services!</p>
                <a href="order.php">
                    <i class="fa-solid fa-shopping-cart"></i>
                    Start Ordering
                </a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): 
                $badge = getStatusBadge($order['status']);
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date">
                                <i class="fa-solid fa-calendar"></i>
                                <?php echo date('F j, Y - g:i A', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <span class="status-badge" style="background: <?php echo $badge['color']; ?>">
                            <i class="fa-solid <?php echo $badge['icon']; ?>"></i>
                            <?php echo $badge['text']; ?>
                        </span>
                    </div>

                    <div class="order-items">
                        <?php foreach($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="item-image"><?php echo htmlspecialchars($item['item_image'] ?? '🍔'); ?></div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    <div class="item-canteen">
                                        <i class="fa-solid fa-store"></i>
                                        <?php echo htmlspecialchars($item['canteen_name']); ?>
                                    </div>
                                    <div class="item-price">
                                        <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                        <span class="item-total">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($order['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax</span>
                            <span>₹<?php echo number_format($order['tax'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>₹<?php echo number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>₹<?php echo number_format($order['total'], 2); ?></span>
                        </div>
                    </div>

                    <div class="order-footer">
                        <div class="delivery-address">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                        </div>
                        <div class="order-actions">
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fa-solid fa-eye"></i>
                                View Details
                            </a>
                            <?php if($order['status'] === 'pending'): ?>
                                <button class="btn btn-secondary" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fa-solid fa-times"></i>
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
// Animate order cards
window.addEventListener('load', function() {
    const cards = document.querySelectorAll('.order-card');
    
    anime({
        targets: cards,
        opacity: [0, 1],
        translateY: [30, 0],
        delay: anime.stagger(100),
        duration: 600,
        easing: 'easeOutQuad'
    });
});

function cancelOrder(orderId) {
    if(confirm('Are you sure you want to cancel this order?')) {
        // Implement cancel order logic
        window.location.href = 'cancel_order.php?id=' + orderId;
    }
}
</script>

</body>
</html>