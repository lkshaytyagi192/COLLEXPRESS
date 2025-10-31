<?php
session_start();
require_once 'db.php';

// Avatar helper functions (same as before)
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

// Get canteen ID from URL
$canteenId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Canteen configurations
$canteens = [
    0 => [
        'name' => 'Green Canteen',
        'emoji' => '🥗',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'description' => 'Healthy & Fresh Meals',
        'menu' => [
            ['id' => 1, 'name' => 'Caesar Salad', 'price' => 120, 'image' => '🥗', 'category' => 'Salads', 'description' => 'Fresh romaine with caesar dressing'],
            ['id' => 2, 'name' => 'Greek Salad', 'price' => 110, 'image' => '🥙', 'category' => 'Salads', 'description' => 'Feta, olives, and fresh veggies'],
            ['id' => 3, 'name' => 'Veggie Wrap', 'price' => 90, 'image' => '🌯', 'category' => 'Wraps', 'description' => 'Grilled vegetables in whole wheat wrap'],
            ['id' => 4, 'name' => 'Smoothie Bowl', 'price' => 150, 'image' => '🥣', 'category' => 'Bowls', 'description' => 'Acai bowl with fresh fruits'],
            ['id' => 5, 'name' => 'Green Juice', 'price' => 80, 'image' => '🥤', 'category' => 'Beverages', 'description' => 'Fresh pressed green juice'],
            ['id' => 6, 'name' => 'Fruit Salad', 'price' => 100, 'image' => '🍇', 'category' => 'Salads', 'description' => 'Seasonal fresh fruits']
        ]
    ],
    1 => [
        'name' => 'Yellow Canteen',
        'emoji' => '🌮',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'description' => 'Mexican & Street Food',
        'menu' => [
            ['id' => 7, 'name' => 'Chicken Tacos', 'price' => 140, 'image' => '🌮', 'category' => 'Tacos', 'description' => 'Grilled chicken with salsa'],
            ['id' => 8, 'name' => 'Veggie Burrito', 'price' => 130, 'image' => '🌯', 'category' => 'Burritos', 'description' => 'Beans, rice, and vegetables'],
            ['id' => 9, 'name' => 'Nachos Supreme', 'price' => 160, 'image' => '🧀', 'category' => 'Snacks', 'description' => 'Loaded nachos with cheese'],
            ['id' => 10, 'name' => 'Quesadilla', 'price' => 120, 'image' => '🫓', 'category' => 'Quesadillas', 'description' => 'Cheese filled tortilla'],
            ['id' => 11, 'name' => 'Mexican Rice Bowl', 'price' => 150, 'image' => '🍚', 'category' => 'Bowls', 'description' => 'Rice with beans and toppings'],
            ['id' => 12, 'name' => 'Churros', 'price' => 80, 'image' => '🍩', 'category' => 'Desserts', 'description' => 'Sweet cinnamon churros']
        ]
    ],
    2 => [
        'name' => 'Red Canteen',
        'emoji' => '🍎',
        'color' => '#ef4444',
        'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)',
        'description' => 'Indian & Traditional',
        'menu' => [
            ['id' => 13, 'name' => 'Paneer Tikka', 'price' => 180, 'image' => '🍢', 'category' => 'Starters', 'description' => 'Grilled cottage cheese'],
            ['id' => 14, 'name' => 'Dal Makhani', 'price' => 140, 'image' => '🍛', 'category' => 'Main Course', 'description' => 'Creamy black lentils'],
            ['id' => 15, 'name' => 'Butter Naan', 'price' => 40, 'image' => '🫓', 'category' => 'Breads', 'description' => 'Buttery flatbread'],
            ['id' => 16, 'name' => 'Biryani', 'price' => 200, 'image' => '🍚', 'category' => 'Rice', 'description' => 'Aromatic rice with vegetables'],
            ['id' => 17, 'name' => 'Masala Chai', 'price' => 30, 'image' => '☕', 'category' => 'Beverages', 'description' => 'Indian spiced tea'],
            ['id' => 18, 'name' => 'Gulab Jamun', 'price' => 60, 'image' => '🍡', 'category' => 'Desserts', 'description' => 'Sweet milk dumplings']
        ]
    ],
    3 => [
        'name' => 'Cafe Da Latte',
        'emoji' => '☕',
        'color' => '#92400e',
        'gradient' => 'linear-gradient(135deg, #92400e, #78350f)',
        'description' => 'Coffee & Beverages',
        'menu' => [
            ['id' => 19, 'name' => 'Cappuccino', 'price' => 100, 'image' => '☕', 'category' => 'Coffee', 'description' => 'Espresso with steamed milk'],
            ['id' => 20, 'name' => 'Latte', 'price' => 110, 'image' => '🥛', 'category' => 'Coffee', 'description' => 'Smooth espresso with milk'],
            ['id' => 21, 'name' => 'Americano', 'price' => 90, 'image' => '☕', 'category' => 'Coffee', 'description' => 'Espresso with hot water'],
            ['id' => 22, 'name' => 'Cold Brew', 'price' => 130, 'image' => '🧊', 'category' => 'Iced Coffee', 'description' => 'Smooth cold coffee'],
            ['id' => 23, 'name' => 'Croissant', 'price' => 80, 'image' => '🥐', 'category' => 'Pastries', 'description' => 'Buttery flaky pastry'],
            ['id' => 24, 'name' => 'Cheesecake', 'price' => 150, 'image' => '🍰', 'category' => 'Desserts', 'description' => 'Creamy cheesecake slice']
        ]
    ]
];

// Validate canteen exists
if(!isset($canteens[$canteenId])) {
    header('Location: order.php');
    exit;
}

$canteen = $canteens[$canteenId];

// Fetch user profile picture
$userProfilePicture = '';
$userQuery = mysqli_query($conn, "SELECT profile_picture FROM users WHERE id = '$userId'");
if($userQuery && mysqli_num_rows($userQuery) > 0) {
    $userData = mysqli_fetch_assoc($userQuery);
    $userProfilePicture = $userData['profile_picture'] ?? '';
}

// Initialize cart in session if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle AJAX cart operations
if(isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    header('Content-Type: application/json');
    
    $itemId = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']) ?: 1;
    
    // Find item in menu
    $item = null;
    foreach($canteen['menu'] as $menuItem) {
        if($menuItem['id'] === $itemId) {
            $item = $menuItem;
            break;
        }
    }
    
    if($item) {
        $cartKey = $canteenId . '_' . $itemId;
        
        if(isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'canteen_id' => $canteenId,
                'canteen_name' => $canteen['name'],
                'item_id' => $itemId,
                'name' => $item['name'],
                'price' => $item['price'],
                'image' => $item['image'],
                'quantity' => $quantity
            ];
        }
        
        echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($canteen['name']); ?> - CollExpress</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: <?php echo $canteen['gradient']; ?>;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1f2937;
    min-height: 100vh;
}

.topbar {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    padding: 1.5rem 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.topbar-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border: 2px solid <?php echo $canteen['color']; ?>;
    color: <?php echo $canteen['color']; ?>;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: <?php echo $canteen['color']; ?>;
    color: white;
    transform: translateX(-5px);
}

.canteen-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.canteen-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: <?php echo $canteen['gradient']; ?>;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.canteen-info h1 {
    font-size: 1.75rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.canteen-info p {
    color: #6b7280;
    font-size: 0.95rem;
}

.cart-btn {
    position: relative;
    padding: 0.75rem 1.5rem;
    background: <?php echo $canteen['gradient']; ?>;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.cart-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 2rem;
}

.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.menu-item {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
}

.menu-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.item-image {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, rgba(0,0,0,0.05), rgba(0,0,0,0.02));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    margin-bottom: 1.25rem;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.75rem;
}

.item-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.item-category {
    background: <?php echo $canteen['color']; ?>20;
    color: <?php echo $canteen['color']; ?>;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.item-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 2px solid #f3f4f6;
}

.item-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: <?php echo $canteen['color']; ?>;
}

.add-to-cart-btn {
    padding: 0.75rem 1.5rem;
    background: <?php echo $canteen['gradient']; ?>;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.add-to-cart-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.add-to-cart-btn:active {
    transform: scale(0.95);
}

.notification {
    position: fixed;
    top: 100px;
    right: 2rem;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    z-index: 1000;
}

.notification.show {
    transform: translateX(0);
}

.notification i {
    color: #10b981;
    font-size: 1.5rem;
}

@media (max-width: 768px) {
    .topbar-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .menu-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .container {
        padding: 2rem 1rem;
    }
}
</style>
</head>
<body>

<div class="topbar">
    <div class="topbar-content">
        <a href="order.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Services
        </a>
        
        <div class="canteen-header">
            <div class="canteen-icon"><?php echo $canteen['emoji']; ?></div>
            <div class="canteen-info">
                <h1><?php echo htmlspecialchars($canteen['name']); ?></h1>
                <p><?php echo htmlspecialchars($canteen['description']); ?></p>
            </div>
        </div>
        
        <button class="cart-btn" onclick="window.location.href='cart.php'">
            <i class="fa-solid fa-shopping-cart"></i>
            View Cart
            <span class="cart-count" id="cartCount"><?php echo count($_SESSION['cart']); ?></span>
        </button>
    </div>
</div>

<div class="container">
    <div class="menu-grid" id="menuGrid">
        <?php foreach($canteen['menu'] as $item): ?>
            <div class="menu-item" data-item-id="<?php echo $item['id']; ?>">
                <div class="item-image"><?php echo $item['image']; ?></div>
                <div class="item-header">
                    <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    <span class="item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                </div>
                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                <div class="item-footer">
                    <span class="item-price">₹<?php echo $item['price']; ?></span>
                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['id']; ?>)">
                        <i class="fa-solid fa-plus"></i>
                        Add to Cart
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="notification" id="notification">
    <i class="fa-solid fa-check-circle"></i>
    <span>Added to cart successfully!</span>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
// Animate menu items on load
window.addEventListener('load', function() {
    const items = document.querySelectorAll('.menu-item');
    
    anime({
        targets: items,
        opacity: [0, 1],
        translateY: [20, 0],
        delay: anime.stagger(100),
        duration: 600,
        easing: 'easeOutQuad'
    });
});

// Add to cart function
function addToCart(itemId) {
    const formData = new FormData();
    formData.append('action', 'add_to_cart');
    formData.append('item_id', itemId);
    formData.append('quantity', 1);
    
    fetch('canteen_menu.php?id=<?php echo $canteenId; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update cart count
            document.getElementById('cartCount').textContent = data.cart_count;
            
            // Show notification
            const notification = document.getElementById('notification');
            notification.classList.add('show');
            
            // Animate button
            const btn = event.target.closest('.add-to-cart-btn');
            anime({
                targets: btn,
                scale: [1, 1.2, 1],
                duration: 400,
                easing: 'easeOutElastic(1, .8)'
            });
            
            // Hide notification after 2 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 2000);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Cart button animation
anime({
    targets: '.cart-btn',
    scale: [0.9, 1],
    opacity: [0, 1],
    duration: 600,
    delay: 300,
    easing: 'easeOutElastic(1, .8)'
});
</script>

</body>
</html>