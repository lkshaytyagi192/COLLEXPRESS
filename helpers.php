<?php
// Avatar Helper Functions - Add this to your db.php or create a separate helpers.php file

function getAvatarInitials($fullName) {
    $names = explode(' ', trim($fullName));
    $initials = '';
    
    foreach ($names as $name) {
        if (!empty($name)) {
            $initials .= strtoupper(substr($name, 0, 1));
            if (strlen($initials) >= 2) break; // Max 2 initials
        }
    }
    
    return $initials ?: 'U'; // Default to 'U' if no name
}

function getAvatarColor($fullName) {
    $colors = [
        '#667eea', '#764ba2', '#f093fb', '#f5576c',
        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
        '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3',
        '#ff9a9e', '#fecfef', '#ffeaa7', '#fab1a0'
    ];
    
    $index = ord(strtolower(substr($fullName, 0, 1))) % count($colors);
    return $colors[$index];
}

function displayAvatar($profilePicture, $fullName, $size = 40, $class = '') {
    $initials = getAvatarInitials($fullName);
    $color = getAvatarColor($fullName);
    
    if (!empty($profilePicture) && file_exists('uploads/avatars/' . $profilePicture)) {
        return '<img src="uploads/avatars/' . htmlspecialchars($profilePicture) . '" 
                     alt="Profile" 
                     class="avatar-img ' . $class . '" 
                     style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover;">';
    } else {
        return '<div class="avatar-initials ' . $class . '" 
                     style="width: ' . $size . 'px; 
                            height: ' . $size . 'px; 
                            background: linear-gradient(135deg, ' . $color . ', ' . adjustBrightness($color, -20) . '); 
                            border-radius: 50%; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center; 
                            color: white; 
                            font-weight: 600;
                            font-size: ' . ($size * 0.4) . 'px;">
                    ' . $initials . '
                </div>';
    }
}

function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($percent * 255 / 100)));
    $g = max(0, min(255, $g + ($percent * 255 / 100)));
    $b = max(0, min(255, $b + ($percent * 255 / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}
?>