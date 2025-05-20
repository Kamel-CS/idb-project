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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 1400px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .form-container {
            background-color: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            margin: 0;
            position: relative;
        }

        h2 {
            color: var(--text-primary);
            margin-bottom: 30px;
            font-size: 1.8rem;
            font-weight: 600;
            text-align: center;
        }

        .form-group {
            margin-bottom: 0;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-darker);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.2);
        }

        select option {
            background-color: var(--bg-darker);
            color: var(--text-primary);
        }

        button[type="submit"] {
            background-color: var(--accent-primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            display: block;
            margin-top: 10px;
        }

        button[type="submit"]:hover {
            background-color: var(--accent-secondary);
            transform: translateY(-1px);
        }

        .navigation {
            position: absolute;
            top: 20px;
            right: 20px;
            margin-top: 0;
        }

        .navigation a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-block;
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .navigation a:hover {
            background-color: var(--accent-primary);
            color: white;
            transform: translateY(-1px);
            border-color: var(--accent-primary);
        }

        .error {
            background-color: var(--danger);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        form {
            display: grid;
            gap: 20px;
        }
    </style>
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