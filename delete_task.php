<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$task_id = $_GET['id'];

// Verify task belongs to user's project and delete it
try {
    // First get the project_id for redirection
    $stmt = $pdo->prepare("
        SELECT t.project_id 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        WHERE t.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
    $task = $stmt->fetch();
    
    if ($task) {
        // Delete the task
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        
        header("Location: project.php?id=" . $task['project_id']);
    } else {
        header("Location: dashboard.php");
    }
} catch(PDOException $e) {
    // Log error if needed
    header("Location: dashboard.php");
}

exit();
?> 