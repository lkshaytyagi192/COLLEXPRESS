<?php
// theme_config_safe.php - SAFE version that won't break existing CSS
if(isset($_SESSION['user_id'])){
    $userId = $_SESSION['user_id'];
    $themeQuery = mysqli_query($conn, "SELECT theme FROM user_settings WHERE user_id='$userId'");
    $themeData = mysqli_fetch_assoc($themeQuery);
    $currentTheme = $themeData['theme'] ?? 'light';
} else {
    $currentTheme = 'light';
}

// Only apply dark theme CLASS - no CSS overrides
if($currentTheme == 'dark') {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.body.classList.add("dark-theme");
        });
    </script>';
}
?>
