<?php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check login
if(!isset($_SESSION['user_id'])){
    header('Location: auth.php');
    exit;
}

// Check if cart is empty
if(!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header('Location: order.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'];

// Get user details from database (with error handling)
$userDetails = ['email' => '', 'phone' => '', 'address' => ''];
$stmt = $conn->prepare("SELECT email, phone, address FROM users WHERE id = ?");
if($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result && $result->num_rows > 0) {
        $userDetails = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle order placement
if(isset($_POST['place_order'])) {
    $deliveryAddress = trim($_POST['delivery_address']);
    $phoneNumber = trim($_POST['phone_number']);
    $paymentMethod = $_POST['payment_method'];
    $deliveryInstructions = trim($_POST['delivery_instructions'] ?? '');
    
    // Validate inputs
    if(empty($deliveryAddress) || empty($phoneNumber)) {
        $error = "Please fill in all required fields.";
    } else {
        // Calculate totals
        $subtotal = 0;
        $itemCount = 0;
        foreach($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
            $itemCount += $item['quantity'];
        }
        
        $tax = $subtotal * 0.05;
        $deliveryFee = 20;
        $total = $subtotal + $tax + $deliveryFee;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert order
            $orderNumber = 'ORD' . time() . rand(1000, 9999);
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, subtotal, tax, delivery_fee, total, delivery_address, phone_number, payment_method, delivery_instructions, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            
            if(!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("isddddssss", $userId, $orderNumber, $subtotal, $tax, $deliveryFee, $total, $deliveryAddress, $phoneNumber, $paymentMethod, $deliveryInstructions);
            
            if(!$stmt->execute()) {
                throw new Exception("Order insert failed: " . $stmt->error);
            }
            
            $orderId = $stmt->insert_id;
            $stmt->close();
            
            // Insert order items
            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, canteen_name, price, quantity, item_image) VALUES (?, ?, ?, ?, ?, ?)");
            
            if(!$itemStmt) {
                throw new Exception("Item prepare failed: " . $conn->error);
            }
            
            foreach($_SESSION['cart'] as $item) {
                $itemStmt->bind_param("issdis", $orderId, $item['name'], $item['canteen_name'], $item['price'], $item['quantity'], $item['image']);
                if(!$itemStmt->execute()) {
                    throw new Exception("Item insert failed: " . $itemStmt->error);
                }
            }
            $itemStmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];
            
            // Redirect to success page
            $_SESSION['order_success'] = [
                'order_number' => $orderNumber,
                'total' => $total
            ];
            header("Location: order_success.php");
            exit;
            
        } catch(Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}

// Calculate totals for display
$subtotal = 0;
$itemCount = 0;
foreach($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
}

$tax = $subtotal * 0.05;
$deliveryFee = 20;
$total = $subtotal + $tax + $deliveryFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - CollExpress</title>
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
    max-width: 1400px;
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
    background: #f3f4f6;
    color: #667eea;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

.header h1 {
    font-size: 2rem;
    color: #1f2937;
}

.checkout-content {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 2rem;
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.section-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.required {
    color: #ef4444;
}

.payment-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.payment-option {
    position: relative;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-option input[type="radio"]:checked + label {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.payment-option i {
    font-size: 2rem;
    color: #667eea;
}

.order-summary {
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
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.order-items {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
}

.order-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.order-item-image {
    font-size: 2rem;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
}

.order-item-details {
    flex: 1;
}

.order-item-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.order-item-canteen {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.order-item-price {
    display: flex;
    justify-content: space-between;
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

.place-order-btn {
    width: 100%;
    padding: 1.25rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 1.5rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.place-order-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.place-order-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.error-message {
    background: #fee2e2;
    color: #dc2626;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

@media (max-width: 1024px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    .order-summary {
        position: static;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-credit-card"></i> Checkout</h1>
        <a href="cart.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Cart
        </a>
    </div>

    <?php if(isset($error)): ?>
    <div class="error-message">
        <i class="fa-solid fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="checkoutForm">
        <div class="checkout-content">
            <div class="checkout-form">
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h2 class="section-title">Delivery Information</h2>
                    </div>
                    
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($userName); ?>" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone_number" name="phone_number" 
                               value="<?php echo htmlspecialchars($userDetails['phone'] ?? ''); ?>" 
                               placeholder="+91 XXXXX XXXXX" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Delivery Address <span class="required">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" 
                                  placeholder="Enter your complete delivery address" 
                                  required><?php echo htmlspecialchars($userDetails['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Delivery Instructions (Optional)</label>
                        <textarea name="delivery_instructions" 
                                  placeholder="Any special instructions for delivery"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <h2 class="section-title">Payment Method</h2>
                    </div>
                    
                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <span>Cash on Delivery</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="upi" name="payment_method" value="upi">
                            <label for="upi">
                                <i class="fa-solid fa-mobile-screen"></i>
                                <span>UPI Payment</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="card" name="payment_method" value="card">
                            <label for="card">
                                <i class="fa-solid fa-credit-card"></i>
                                <span>Card</span>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="wallet" name="payment_method" value="wallet">
                            <label for="wallet">
                                <i class="fa-solid fa-wallet"></i>
                                <span>Wallet</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-summary">
                <h2 class="summary-title">Order Summary</h2>
                
                <div class="order-items">
                    <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="order-item">
                            <div class="order-item-image"><?php echo $item['image']; ?></div>
                            <div class="order-item-details">
                                <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="order-item-canteen">
                                    <?php echo htmlspecialchars($item['canteen_name']); ?>
                                </div>
                                <div class="order-item-price">
                                    <span>Qty: <?php echo $item['quantity']; ?></span>
                                    <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (5%)</span>
                    <span>₹<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>₹<?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
                
                <button type="submit" name="place_order" class="place-order-btn" id="submitBtn">
                    <i class="fa-solid fa-check-circle"></i>
                    Place Order
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    const phone = document.getElementById('phone_number').value.trim();
    const address = document.getElementById('delivery_address').value.trim();
    
    if(phone.length < 10) {
        e.preventDefault();
        alert('Please enter a valid phone number');
        return false;
    }
    
    if(address.length < 5) {
        e.preventDefault();
        alert('Please enter your delivery address');
        return false;
    }
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
});

document.getElementById('phone_number').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});
</script>

</body>
</html>