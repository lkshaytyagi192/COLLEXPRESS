<?php
session_start();
require_once 'db.php';

// Check login
if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Initialize cart if not exists
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart operations
if(isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if($_POST['action'] === 'update_quantity') {
        $cartKey = $_POST['cart_key'];
        $quantity = intval($_POST['quantity']);
        
        if($quantity > 0 && isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    if($_POST['action'] === 'remove_item') {
        $cartKey = $_POST['cart_key'];
        
        if(isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    if($_POST['action'] === 'clear_cart') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
        exit;
    }
}

// Calculate totals
$subtotal = 0;
$itemCount = 0;
foreach($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
}

$tax = $subtotal * 0.05; // 5% tax
$deliveryFee = $subtotal > 0 ? 20 : 0;
$total = $subtotal + $tax + $deliveryFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart - CollExpress</title>
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
    padding: 2rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.header h1 {
    font-size: 2rem;
    color: #1f2937;
}

.cart-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.cart-item {
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: grid;
    grid-template-columns: 100px 1fr auto;
    gap: 1.5rem;
    align-items: center;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
}

.cart-item:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.item-image {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, rgba(0,0,0,0.05), rgba(0,0,0,0.02));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
}

.item-details {
    flex: 1;
}

.item-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.item-canteen {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.item-price {
    font-size: 1.1rem;
    font-weight: 600;
    color: #667eea;
}

.item-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-end;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f3f4f6;
    padding: 0.5rem;
    border-radius: 10px;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: white;
    color: #667eea;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    transition: all 0.3s ease;
}

.qty-btn:hover {
    background: #667eea;
    color: white;
    transform: scale(1.1);
}

.qty-input {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 1rem;
}

.remove-btn {
    padding: 0.5rem 1rem;
    background: #fee2e2;
    color: #ef4444;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.remove-btn:hover {
    background: #ef4444;
    color: white;
}

.cart-summary {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    height: fit-content;
    position: sticky;
    top: 2rem;
}

.summary-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #1f2937;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    color: #6b7280;
}

.summary-row.total {
    border-top: 2px solid #e5e7eb;
    margin-top: 1rem;
    padding-top: 1rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.checkout-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: all 0.3s ease;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.clear-cart-btn {
    width: 100%;
    padding: 0.75rem;
    background: #fee2e2;
    color: #ef4444;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.clear-cart-btn:hover {
    background: #ef4444;
    color: white;
}

.empty-cart {
    background: white;
    padding: 4rem 2rem;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.empty-cart i {
    font-size: 5rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-cart h2 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.empty-cart p {
    color: #6b7280;
    margin-bottom: 2rem;
}

@media (max-width: 968px) {
    .cart-content {
        grid-template-columns: 1fr;
    }
    
    .cart-summary {
        position: static;
    }
    
    .cart-item {
        grid-template-columns: 80px 1fr;
    }
    
    .item-actions {
        grid-column: 2;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-shopping-cart"></i> Shopping Cart</h1>
        <a href="order.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Continue Shopping
        </a>
    </div>

    <?php if(count($_SESSION['cart']) > 0): ?>
    <div class="cart-content">
        <div class="cart-items" id="cartItems">
            <?php foreach($_SESSION['cart'] as $key => $item): ?>
                <div class="cart-item" data-cart-key="<?php echo $key; ?>">
                    <div class="item-image"><?php echo $item['image']; ?></div>
                    <div class="item-details">
                        <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="item-canteen">
                            <i class="fa-solid fa-store"></i> 
                            <?php echo htmlspecialchars($item['canteen_name']); ?>
                        </p>
                        <p class="item-price">₹<?php echo $item['price']; ?> each</p>
                    </div>
                    <div class="item-actions">
                        <div class="quantity-control">
                            <button class="qty-btn" onclick="updateQuantity('<?php echo $key; ?>', -1)">-</button>
                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" 
                                   id="qty-<?php echo $key; ?>" min="1" readonly>
                            <button class="qty-btn" onclick="updateQuantity('<?php echo $key; ?>', 1)">+</button>
                        </div>
                        <button class="remove-btn" onclick="removeItem('<?php echo $key; ?>')">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h2 class="summary-title">Order Summary</h2>
            <div class="summary-row">
                <span>Subtotal (<?php echo $itemCount; ?> items)</span>
                <span id="subtotal">₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Tax (5%)</span>
                <span id="tax">₹<?php echo number_format($tax, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span id="delivery">₹<?php echo number_format($deliveryFee, 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="total">₹<?php echo number_format($total, 2); ?></span>
            </div>
            <button class="checkout-btn" onclick="checkout()">
                <i class="fa-solid fa-lock"></i> Proceed to Checkout
            </button>
            <button class="clear-cart-btn" onclick="clearCart()">
                <i class="fa-solid fa-trash-can"></i> Clear Cart
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-cart">
        <i class="fa-solid fa-shopping-cart"></i>
        <h2>Your cart is empty</h2>
        <p>Add some delicious items to get started!</p>
        <a href="order.php" class="back-btn">
            <i class="fa-solid fa-utensils"></i>
            Browse Menu
        </a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
// Animate cart items
window.addEventListener('load', function() {
    const items = document.querySelectorAll('.cart-item');
    
    anime({
        targets: items,
        opacity: [0, 1],
        translateY: [20, 0],
        delay: anime.stagger(100),
        duration: 600,
        easing: 'easeOutQuad'
    });
});

function updateQuantity(cartKey, change) {
    const qtyInput = document.getElementById('qty-' + cartKey);
    let newQty = parseInt(qtyInput.value) + change;
    
    if(newQty < 1) return;
    
    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('cart_key', cartKey);
    formData.append('quantity', newQty);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            qtyInput.value = newQty;
            location.reload();
        }
    });
}

function removeItem(cartKey) {
    if(!confirm('Remove this item from cart?')) return;
    
    const formData = new FormData();
    formData.append('action', 'remove_item');
    formData.append('cart_key', cartKey);
    
    const item = document.querySelector(`[data-cart-key="${cartKey}"]`);
    
    anime({
        targets: item,
        opacity: 0,
        translateX: -50,
        duration: 400,
        easing: 'easeInQuad',
        complete: function() {
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            });
        }
    });
}

function clearCart() {
    if(!confirm('Clear all items from cart?')) return;
    
    const formData = new FormData();
    formData.append('action', 'clear_cart');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}

function checkout() {
    window.location.href = 'checkout.php';
}
</script>

</body>
</html>