<?php
session_start();

// Store user info before destroying session for personalized message
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Optional: Clear any remember me cookies if you have them
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - CollExpress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background Elements */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: -2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: -4s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 25%;
            animation-delay: -1s;
        }
        
        .shape:nth-child(5) {
            width: 90px;
            height: 90px;
            bottom: 30%;
            right: 30%;
            animation-delay: -3s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.5;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.8;
            }
        }
        
        /* Main Container */
        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            position: relative;
            z-index: 2;
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo and Header */
        .logo {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);
            }
        }
        
        /* Content */
        .logout-content h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .logout-content p {
            color: #6b7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        
        .user-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            border-left: 4px solid #667eea;
        }
        
        .user-info p {
            color: #374151;
            font-weight: 500;
        }
        
        /* Action Buttons */
        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 0.875rem 2rem;
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
            min-width: 150px;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        /* Security Notice */
        .security-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
        }
        
        .security-notice p {
            color: #92400e;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Footer */
        .logout-footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 0.875rem;
        }
        
        .logout-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .logout-footer a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }
        
        /* Auto redirect notification */
        .redirect-notice {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            font-size: 0.875rem;
            z-index: 1000;
            animation: slideInRight 0.5s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .logout-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .logout-content h1 {
                font-size: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .redirect-notice {
                top: 10px;
                right: 10px;
                left: 10px;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .logout-container {
                padding: 1.5rem 1rem;
            }
            
            .logout-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Main Logout Container -->
    <div class="logout-container">
        <!-- Logo -->
        <div class="logo">CollExpress</div>
        
        <!-- Logout Icon -->
        <div class="logout-icon">
            <i class="fa-solid fa-check"></i>
        </div>
        
        <!-- Content -->
        <div class="logout-content">
            <h1>You've been logged out successfully!</h1>
            <p>Thank you for using CollExpress.</p>
            
            <?php if (!empty($user_name) && $user_name !== 'User'): ?>
            <div class="user-info">
                <p>Goodbye, <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                <?php if (!empty($user_email)): ?>
                    <p style="font-size: 0.9rem; color: #6b7280; margin-top: 0.25rem;">
                        <?php echo htmlspecialchars($user_email); ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <p>Your session has been securely ended and all data cleared.</p>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="auth.php" class="btn btn-primary">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login Again
            </a>
            <a href="index.html" class="btn btn-secondary">
                <i class="fa-solid fa-house"></i>
                Go to Homepage
            </a>
        </div>
        
        <!-- Security Notice -->
        <div class="security-notice">
            <p>
                <i class="fa-solid fa-shield-halved"></i>
                If you're on a public computer, make sure to close all browser windows.
            </p>
        </div>
        
        <!-- Footer -->
        <div class="logout-footer">
            <p>
                Need help? <a href="contact.php">Contact Support</a> | 
                <a href="privacy.php">Privacy Policy</a>
            </p>
        </div>
    </div>

    <!-- Auto Redirect Notice -->
    <div class="redirect-notice" id="redirectNotice">
        <i class="fa-solid fa-clock"></i>
        Redirecting to login in <span id="countdown">10</span> seconds
    </div>

    <script>
        // Auto redirect countdown
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        const redirectNotice = document.getElementById('redirectNotice');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'auth.php';
            }
        }, 1000);
        
        // Hide redirect notice on click
        redirectNotice.addEventListener('click', () => {
            clearInterval(timer);
            redirectNotice.style.display = 'none';
        });
        
        // Clear any remaining data from localStorage/sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
        } catch (e) {
            console.log('Storage clearing not supported or failed');
        }
        
        // Prevent back button from accessing protected pages
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
        
        // Optional: Show confirmation if user tries to leave
        window.addEventListener('beforeunload', function(e) {
            // Only show if they haven't clicked login again
            if (!document.activeElement.href || !document.activeElement.href.includes('auth.php')) {
                e.preventDefault();
                return 'You have been logged out. Are you sure you want to leave?';
            }
        });
        
        // Add smooth scroll animation on load
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>