<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['accept_fare']) || isset($_POST['reject_fare'])) {
    $proposal_id = intval($_POST['proposal_id']);

    // Fetch proposal
    $res = mysqli_query($conn, "SELECT * FROM proposals WHERE id='$proposal_id' AND user_id='$user_id'");
    if(mysqli_num_rows($res) == 0) {
        die("Invalid proposal.");
    }
    $proposal = mysqli_fetch_assoc($res);

    if(isset($_POST['accept_fare'])) {
        // Update proposal budget and status
        $raised = floatval($_POST['raised_fare']);
        mysqli_query($conn, "UPDATE proposals SET budget='$raised', status='accepted' WHERE id='$proposal_id'");
    } else {
        // Reject fare raise → keep old budget, status remains pending
        mysqli_query($conn, "UPDATE proposals SET status='pending' WHERE id='$proposal_id'");
    }
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
?>
