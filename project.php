<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$project_id = $_GET['id'];

// Verify project belongs to user
try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Failed to fetch project";
}

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_task') {
        $title = trim($_POST['task_title']);
        $description = trim($_POST['task_description']);
        $priority = $_POST['priority'];
        $due_date = $_POST['due_date'];
        $parent_task_id = !empty($_POST['parent_task_id']) ? $_POST['parent_task_id'] : null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, parent_task_id, title, description, priority, due_date) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $parent_task_id, $title, $description, $priority, $due_date]);
        } catch(PDOException $e) {
            $error = "Failed to create task";
        }
    } elseif ($_POST['action'] == 'toggle_task' && isset($_POST['task_id'])) {
        try {
            // Get current task status
            $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ? AND project_id = ?");
            $stmt->execute([$_POST['task_id'], $project_id]);
            $task = $stmt->fetch();
            
            if ($task) {
                $new_status = $task['status'] == 'completed' ? 'pending' : 'completed';
                
                // Update task status
                $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND project_id = ?");
                $stmt->execute([$new_status, $_POST['task_id'], $project_id]);
                
                // Debug output - you can remove this after confirming it works
                error_log("Task status updated: " . $new_status . " for task ID: " . $_POST['task_id']);
            }
        } catch(PDOException $e) {
            $error = "Failed to update task status";
            error_log("Error updating task status: " . $e->getMessage());
        }
    }
}

// Fetch tasks for this project with hierarchy
try {
    // First, get all tasks
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project_id]);
    $all_tasks = $stmt->fetchAll();
    
    // Organize tasks into hierarchy
    $tasks = [];
    $subtasks = [];
    
    foreach ($all_tasks as $task) {
        if ($task['parent_task_id'] === null) {
            $tasks[] = $task;
        } else {
            if (!isset($subtasks[$task['parent_task_id']])) {
                $subtasks[$task['parent_task_id']] = [];
            }
            $subtasks[$task['parent_task_id']][] = $task;
        }
    }
} catch(PDOException $e) {
    $error = "Failed to fetch tasks";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project - Task Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #1a1a1a;
            --bg-darker: #121212;
            --bg-card: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-primary: #6c5ce7;
            --accent-secondary: #a29bfe;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --border-color: #404040;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--bg-card);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: var(--accent-primary);
        }

        .project-header {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .project-title {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .project-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
        }

        .task-form {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .task-form input,
        .task-form select,
        .task-form textarea {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .task-form input:focus,
        .task-form select:focus,
        .task-form textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .task-form button {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .task-form button:hover {
            background-color: var(--accent-secondary);
        }

        .task-list {
            display: grid;
            gap: 15px;
        }

        .task-item {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .task-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .task-title {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .task-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .task-meta {
            display: flex;
            gap: 15px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .task-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            color: white;
        }

        .complete-btn {
            background-color: var(--success);
        }

        .edit-btn {
            background-color: var(--warning);
        }

        .delete-btn {
            background-color: var(--danger);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: var(--warning);
            color: #000;
        }

        .status-completed {
            background-color: var(--success);
            color: white;
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .priority-high {
            background-color: var(--danger);
            color: white;
        }

        .priority-medium {
            background-color: var(--warning);
            color: #000;
        }

        .priority-low {
            background-color: var(--success);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="project-header">
            <h1 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h1>
            <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
        </div>

        <div class="task-form">
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_task">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                <input type="text" name="task_name" placeholder="Task name" required>
                <textarea name="task_description" placeholder="Task description" rows="3"></textarea>
                <select name="priority" required>
                    <option value="">Select Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <input type="date" name="due_date">
                <button type="submit">
                    <i class="fas fa-plus"></i> Add Task
                </button>
            </form>
        </div>

        <div class="task-list">
            <?php foreach ($tasks as $task): ?>
                <div class="task-item">
                    <div class="task-header">
                        <h3 class="task-title"><?php echo htmlspecialchars($task['name']); ?></h3>
                        <span class="status-badge status-<?php echo $task['status']; ?>">
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                    </div>
                    
                    <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                    
                    <div class="task-meta">
                        <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                            <?php echo ucfirst($task['priority']); ?> Priority
                        </span>
                        <?php if ($task['due_date']): ?>
                            <span>
                                <i class="fas fa-calendar"></i>
                                Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="task-actions">
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="action" value="toggle_task">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" class="action-btn complete-btn">
                                <i class="fas fa-check"></i> <?php echo $task['status'] == 'completed' ? 'Reopen' : 'Complete'; ?>
                            </button>
                        </form>
                        <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="action-btn edit-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_task.php?id=<?php echo $task['id']; ?>" 
                           class="action-btn delete-btn"
                           onclick="return confirm('Are you sure you want to delete this task?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 