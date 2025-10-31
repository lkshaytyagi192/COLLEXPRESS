<?php
session_start();

// Check if there's a successful order
if(!isset($_SESSION['order_success'])) {
    header('Location: order.php');
    exit;
}

$orderData = $_SESSION['order_success'];
unset($_SESSION['order_success']); // Clear the session data
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Successful - CollExpress</title>
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
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.success-container {
    background: white;
    border-radius: 24px;
    padding: 3rem;
    max-width: 600px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.success-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    animation: scaleIn 0.5s ease-out;
}

.success-icon i {
    font-size: 4rem;
    color: white;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

h1 {
    font-size: 2rem;
    color: #1f2937;
    margin-bottom: 1rem;
}

.order-number {
    font-size: 1.5rem;
    color: #667eea;
    font-weight: 700;
    margin-bottom: 1rem;
}

.success-message {
    color: #6b7280;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.order-total {
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.order-total-label {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.order-total-amount {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    padding: 1rem 2rem;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f3f4f6;
    color: #667eea;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

@media (max-width: 640px) {
    .success-container {
        padding: 2rem;
    }
    
    .actions {
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

<div class="success-container">
    <div class="success-icon">
        <i class="fa-solid fa-check"></i>
    </div>
    
    <h1>Order Placed Successfully!</h1>
    
    <div class="order-number"><?php echo htmlspecialchars($orderData['order_number']); ?></div>
    
    <p class="success-message">
        Thank you for your order! Your order has been placed successfully and is being processed. 
        You'll receive updates about your order status.
    </p>
    
    <div class="order-total">
        <div class="order-total-label">Total Amount</div>
        <div class="order-total-amount">₹<?php echo number_format($orderData['total'], 2); ?></div>
    </div>
    
    <div class="actions">
        <a href="my_orders.php" class="btn btn-primary">
            <i class="fa-solid fa-box"></i>
            View My Orders
        </a>
        <a href="order.php" class="btn btn-secondary">
            <i class="fa-solid fa-shopping-cart"></i>
            Order Again
        </a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
window.addEventListener('load', function() {
    anime({
        targets: '.success-container',
        opacity: [0, 1],
        translateY: [30, 0],
        duration: 600,
        easing: 'easeOutQuad'
    });
});
</script>

</body>
</html>