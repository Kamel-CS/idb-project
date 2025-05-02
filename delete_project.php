<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$project_id = $_GET['id'];

// Verify project belongs to user and delete it
try {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
} catch(PDOException $e) {
    // Log error if needed
}

header("Location: dashboard.php");
exit();
?> 