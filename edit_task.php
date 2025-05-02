<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$task_id = $_GET['id'];

// Verify task belongs to user's project
try {
    $stmt = $pdo->prepare("
        SELECT t.*, p.user_id 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        WHERE t.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
    $task = $stmt->fetch();
    
    if (!$task) {
        header("Location: dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Failed to fetch task";
}

// Handle task update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['task_title']);
    $description = trim($_POST['task_description']);
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $parent_task_id = !empty($_POST['parent_task_id']) ? $_POST['parent_task_id'] : null;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, parent_task_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $parent_task_id, $task_id]);
        header("Location: project.php?id=" . $task['project_id']);
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update task";
    }
}

// Fetch available parent tasks
try {
    $stmt = $pdo->prepare("
        SELECT id, title 
        FROM tasks 
        WHERE project_id = ? AND id != ? 
        ORDER BY title
    ");
    $stmt->execute([$task['project_id'], $task_id]);
    $parent_tasks = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Failed to fetch parent tasks";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Task Manager</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Edit Task</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="task_title">Task Title:</label>
                    <input type="text" id="task_title" name="task_title" 
                           value="<?php echo htmlspecialchars($task['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="task_description">Description:</label>
                    <input type="text" id="task_description" name="task_description" 
                           value="<?php echo htmlspecialchars($task['description']); ?>">
                </div>
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority">
                        <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="due_date">Due Date:</label>
                    <input type="date" id="due_date" name="due_date" 
                           value="<?php echo $task['due_date']; ?>">
                </div>
                <div class="form-group">
                    <label for="parent_task_id">Parent Task (optional):</label>
                    <select id="parent_task_id" name="parent_task_id">
                        <option value="">None</option>
                        <?php foreach ($parent_tasks as $parent_task): ?>
                            <option value="<?php echo $parent_task['id']; ?>" 
                                    <?php echo $task['parent_task_id'] == $parent_task['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent_task['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Update Task</button>
            </form>
            <div class="navigation">
                <a href="project.php?id=<?php echo $task['project_id']; ?>">Back to Project</a>
            </div>
        </div>
    </div>
</body>
</html> 