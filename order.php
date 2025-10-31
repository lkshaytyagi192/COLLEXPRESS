<?php
session_start();
require_once 'db.php';

// Avatar helper functions
function getAvatarInitials($fullName) { /* same as before */ }
function getAvatarColor($fullName) { /* same as before */ }
function adjustBrightness($hex, $percent) { /* same as before */ }
function displayAvatar($profilePicture, $fullName, $size = 40) { /* same as before */ }

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

// Orders options
$options = [
    ['name'=>'Green Canteen','emoji'=>'🥗','color'=>'#10b981','gradient'=>'linear-gradient(135deg, #10b981, #059669)','icon'=>'fa-leaf'],
    ['name'=>'Yellow Canteen','emoji'=>'🌮','color'=>'#f59e0b','gradient'=>'linear-gradient(135deg, #f59e0b, #d97706)','icon'=>'fa-utensils'],
    ['name'=>'Red Canteen','emoji'=>'🍎','color'=>'#ef4444','gradient'=>'linear-gradient(135deg, #ef4444, #dc2626)','icon'=>'fa-apple-whole'],
    ['name'=>'Cafe Da Latte','emoji'=>'☕','color'=>'#92400e','gradient'=>'linear-gradient(135deg, #92400e, #78350f)','icon'=>'fa-mug-hot'],
    ['name'=>'Stationary','emoji'=>'🖊️','color'=>'#3b82f6','gradient'=>'linear-gradient(135deg, #3b82f6, #2563eb)','icon'=>'fa-pen'],
    ['name'=>'Rent Electricals','emoji'=>'⚡','color'=>'#eab308','gradient'=>'linear-gradient(135deg, #eab308, #ca8a04)','icon'=>'fa-bolt']
];

// Initialize cart if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Services - CollExpress</title>
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
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1f2937;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
}

/* Animated background particles */
body::before {
    content: '';
    position: fixed;
    width: 200%;
    height: 200%;
    top: -50%;
    left: -50%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: moveBackground 20s linear infinite;
    z-index: 0;
}

@keyframes moveBackground {
    0% { transform: translate(0, 0); }
    100% { transform: translate(50px, 50px); }
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
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.sidebar a:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    color: #667eea;
    transform: translateX(8px);
}

.sidebar a:hover::before {
    transform: scaleY(1);
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
    position: relative;
    z-index: 1;
    min-height: 100vh;
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
    border: 1px solid rgba(255, 255, 255, 0.5);
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

.topbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cart-button {
    position: relative;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.cart-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.cart-button i {
    font-size: 1.1rem;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ef4444;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    animation: pulse 2s ease-in-out infinite;
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
    border: 2px solid transparent;
}

.profile:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: rgba(102, 126, 234, 0.3);
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
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.profile-info .status::before {
    content: '';
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Order cards container */
.order-header {
    margin-bottom: 2rem;
    text-align: center;
}

.order-header h2 {
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.order-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.05rem;
}

.orders {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    padding: 1rem;
}

.order-card {
    background: white;
    border-radius: 24px;
    padding: 2.5rem 2rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
    opacity: 0;
    transform: translateY(30px);
}

.order-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: var(--card-gradient);
    transform: scaleX(0);
    transition: transform 0.4s ease;
}

.order-card:hover::before {
    transform: scaleX(1);
}

.order-card-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: var(--card-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    position: relative;
}

.order-card-icon::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: inherit;
    filter: blur(20px);
    opacity: 0.5;
    z-index: -1;
}

.order-card-icon .emoji {
    font-size: 2.5rem;
}

.order-card-icon i {
    font-size: 2.2rem;
    color: white;
}

.order-card h3 {
    color: #1f2937;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.order-card p {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
}

.order-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
    border-color: var(--card-color);
}

.order-card-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #10b981;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    opacity: 0;
    transform: translateX(10px);
    transition: all 0.3s ease;
}

.order-card:hover .order-card-badge {
    opacity: 1;
    transform: translateX(0);
}

/* Loading animation */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.loading-overlay.hidden {
    opacity: 0;
    visibility: hidden;
}

.loader {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
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
        transition: transform 0.3s ease;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main {
        margin-left: 0;
        padding: 1.5rem;
    }
    
    .topbar {
        flex-direction: column;
        gap: 1rem;
        padding: 1.5rem;
    }
    
    .topbar-right {
        width: 100%;
        justify-content: space-between;
    }
    
    .orders {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .order-header h2 {
        font-size: 1.5rem;
    }
}
</style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loader"></div>
</div>

<div class="sidebar">
    <div class="sidebar-logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <h2>CollExpress</h2>
    </div>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="my_proposals.php"><i class="fa-solid fa-briefcase"></i> My Proposals</a>
    <a href="order.php" class="active"><i class="fa-solid fa-cart-shopping"></i> Order Services</a>
    <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <h1>Order Services</h1>
            <p>Choose a service to get started</p>
        </div>
        <div class="topbar-right">
            <a href="cart.php" class="cart-button">
                <i class="fa-solid fa-shopping-cart"></i>
                <span>Cart</span>
                <?php if(count($_SESSION['cart']) > 0): ?>
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                <?php endif; ?>
            </a>
            <div class="profile" onclick="window.location.href='profile.php'">
                <?php echo displayAvatar($userProfilePicture, $userName, 45); ?>
                <div class="profile-info">
                    <span class="name"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="status">Online</span>
                </div>
            </div>
        </div>
    </div>

    <div class="order-header">
        <h2>From where would you like to order?</h2>
        <p>Select from our range of campus services</p>
    </div>

    <div class="orders" id="ordersContainer">
        <?php foreach($options as $index => $opt): ?>
            <div class="order-card" 
                 style="--card-gradient: <?php echo $opt['gradient']; ?>; --card-color: <?php echo $opt['color']; ?>;"
                 data-index="<?php echo $index; ?>">
                <div class="order-card-icon">
                    <span class="emoji"><?php echo $opt['emoji']; ?></span>
                </div>
                <h3><?php echo htmlspecialchars($opt['name']); ?></h3>
                <p>Quick and reliable service</p>
                <span class="order-card-badge">Available</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
// Hide loading overlay
window.addEventListener('load', function() {
    setTimeout(() => {
        document.getElementById('loadingOverlay').classList.add('hidden');
        animateCards();
    }, 500);
});

// Animate cards on load
function animateCards() {
    const cards = document.querySelectorAll('.order-card');
    
    anime({
        targets: cards,
        opacity: [0, 1],
        translateY: [30, 0],
        scale: [0.9, 1],
        delay: anime.stagger(100, {start: 200}),
        duration: 800,
        easing: 'easeOutElastic(1, .8)'
    });
}

// Card hover and click animations
document.querySelectorAll('.order-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        anime({
            targets: this.querySelector('.order-card-icon'),
            rotate: [0, 360],
            scale: [1, 1.1, 1],
            duration: 600,
            easing: 'easeOutElastic(1, .6)'
        });
        
        anime({
            targets: this.querySelector('.emoji'),
            scale: [1, 1.2, 1],
            duration: 500,
            easing: 'easeOutElastic(1, .8)'
        });
    });
    
    card.addEventListener('click', function() {
        const index = this.dataset.index;
        
        // Pulse animation on click
        anime({
            targets: this,
            scale: [1, 0.95, 1],
            duration: 300,
            easing: 'easeInOutQuad',
            complete: function() {
                // Navigate to canteen menu page
                window.location.href = 'canteen_menu.php?id=' + index;
            }
        });
    });
});

// Animate sidebar links
anime({
    targets: '.sidebar a',
    translateX: [-20, 0],
    opacity: [0, 1],
    delay: anime.stagger(50, {start: 100}),
    duration: 600,
    easing: 'easeOutQuad'
});

// Topbar animation
anime({
    targets: '.topbar',
    translateY: [-20, 0],
    opacity: [0, 1],
    duration: 800,
    delay: 300,
    easing: 'easeOutQuad'
});

// Floating animation for icons
anime({
    targets: '.order-card-icon',
    translateY: [-5, 5],
    duration: 2000,
    loop: true,
    direction: 'alternate',
    easing: 'easeInOutSine',
    delay: anime.stagger(200)
});

// Cart button animation
anime({
    targets: '.cart-button',
    scale: [0.8, 1],
    opacity: [0, 1],
    duration: 600,
    delay: 400,
    easing: 'easeOutElastic(1, .8)'
});
</script>

</body>
</html>